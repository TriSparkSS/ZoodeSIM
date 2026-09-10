<?php

namespace App\Services\Esim;

use App\DataTransferObjects\EsimPackageData;
use App\DataTransferObjects\EsimPriceQuote;
use App\DataTransferObjects\PurchaseOffer;
use App\Exceptions\EsimPurchaseException;
use App\Exceptions\ResellPortalException;
use App\Models\EsimOrder;
use App\Models\EsimOrderDetail;
use App\Models\User;
use App\Services\Esim\Contracts\EsimOrderServiceInterface;
use App\Services\Esim\Contracts\EsimPackageServiceInterface;
use App\Services\Esim\Contracts\EsimPaymentGatewayInterface;
use App\Services\Esim\Contracts\EsimPricingServiceInterface;
use App\Services\Esim\Contracts\ResellPortalUserClientServiceInterface;
use App\Services\Referral\Contracts\PurchaseOfferServiceInterface;
use App\Services\Referral\Contracts\PurchaseSettlementServiceInterface;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use App\Support\ApiLogContext;
use App\Support\ResellPortalProviderId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EsimOrderService implements EsimOrderServiceInterface
{
    public function __construct(
        protected EsimPackageServiceInterface $packages,
        protected EsimPricingServiceInterface $pricing,
        protected EsimPaymentGatewayInterface $payments,
        protected ResellPortalUserClientServiceInterface $clients,
        protected ResellPortalClientInterface $provider,
        protected PurchaseOfferServiceInterface $offers,
        protected PurchaseSettlementServiceInterface $settlement,
        protected ApiLogContext $logContext,
        protected EsimOrderQueryService $queries,
    ) {}

    public function purchase(User $user, string $packageCode, ?string $idempotencyKey = null): EsimOrder
    {
        $this->logContext->forUser($user->id);

        $package = $this->packages->findByCode($packageCode);

        if ($package === null) {
            throw new EsimPurchaseException('api.esim.package_not_found', 404, 'eSIM package not found');
        }

        $quote = $this->pricing->quote($package);
        $offer = $this->offers->offerFor($user, $quote);
        $key = $this->normalizeIdempotencyKey($idempotencyKey);
        $clientId = $this->clients->resolve($user);
        $order = $this->firstOrCreateLocalOrder($user, $package, $quote, $offer, $clientId, $key);
        $this->logContext->forReference('order', $order->id);

        return $this->advance($order);
    }

    public function findOwned(User $user, string $orderId): EsimOrder
    {
        $order = EsimOrder::query()
            ->with('detail')
            ->where('user_id', $user->id)
            ->whereKey($orderId)
            ->first();

        if ($order === null) {
            throw new EsimPurchaseException('api.esim.unauthorized_order', 404, 'eSIM order not found for user');
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EsimOrder>
     */
    public function listOwned(User $user, array $filters = []): Collection
    {
        return $this->queries->filteredQuery($filters)
            ->with('detail')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    protected function advance(EsimOrder $order): EsimOrder
    {
        $order->loadMissing('detail');

        if ($order->isActive() && $order->detail) {
            Log::info('eSIM order already processed', ['order_id' => $order->id]);

            return $order;
        }

        if ($order->isFailed()) {
            throw new EsimPurchaseException('api.esim.purchase_failed', 422, 'eSIM order previously failed');
        }

        if ($order->order_status === EsimOrder::STATUS_PROVISIONING) {
            if ($order->resellportal_service_id && $order->detail) {
                return $order;
            }

            throw new EsimPurchaseException('api.esim.already_processed', 409, 'eSIM order provisioning already in progress');
        }

        if ($order->payment_status !== EsimOrder::PAYMENT_PAID) {
            $this->settlePayment($order);
        }

        if (! $this->claimForProvisioning($order)) {
            $order->refresh()->load('detail');

            if ($order->isActive() && $order->detail) {
                return $order;
            }

            throw new EsimPurchaseException('api.esim.already_processed', 409, 'eSIM order already claimed');
        }

        return $this->provision($order->fresh());
    }

    protected function settlePayment(EsimOrder $order): void
    {
        Log::info('eSIM payment verification started', ['order_id' => $order->id]);

        $result = $this->payments->settle($order);

        if (! $result->successful) {
            $order->update([
                'payment_status' => EsimOrder::PAYMENT_FAILED,
                'order_status' => EsimOrder::STATUS_FAILED,
                'failure_reason' => $result->reason ?? 'payment_failed',
            ]);

            Log::info('eSIM payment verification failed', [
                'order_id' => $order->id,
                'reason' => $result->reason,
            ]);

            $messageKey = $result->reason === 'insufficient_balance'
                ? 'api.esim.insufficient_balance'
                : 'api.esim.payment_failed';

            throw new EsimPurchaseException($messageKey, 402, 'eSIM payment verification failed');
        }

        $order->update([
            'payment_status' => EsimOrder::PAYMENT_PAID,
            'order_status' => EsimOrder::STATUS_PAID,
        ]);
        $order->refresh();

        $this->settlement->settle($order);

        Log::info('eSIM payment verified', ['order_id' => $order->id]);
    }

    protected function claimForProvisioning(EsimOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $fresh = EsimOrder::query()->whereKey($order->id)->lockForUpdate()->first();

            if ($fresh === null || $fresh->resellportal_service_id || $fresh->order_status === EsimOrder::STATUS_PROVISIONING) {
                return false;
            }

            $fresh->update(['order_status' => EsimOrder::STATUS_PROVISIONING]);

            return true;
        });
    }

    protected function provision(EsimOrder $order): EsimOrder
    {
        Log::info('ResellPortal eSIM order requested', [
            'order_id' => $order->id,
            'package_code' => $order->package_code,
        ]);

        try {
            $clientId = ResellPortalProviderId::from($order->resellportal_client_id);

            if ($clientId === null) {
                $order->update([
                    'order_status' => EsimOrder::STATUS_FAILED,
                    'failure_reason' => 'missing_client_id',
                ]);

                throw new EsimPurchaseException('api.esim.provisioning_failed', 502, 'ResellPortal client_id missing');
            }

            $payload = $this->provider->createEsimOrder(
                $clientId,
                $order->package_code,
            );
        } catch (ResellPortalException $e) {
            $order->update([
                'order_status' => EsimOrder::STATUS_FAILED,
                'failure_reason' => 'provider_'.$e->reason,
            ]);

            Log::warning('ResellPortal eSIM order failed', [
                'order_id' => $order->id,
                'reason' => $e->reason,
            ]);

            throw new EsimPurchaseException('api.esim.provisioning_failed', 502, 'ResellPortal eSIM provisioning failed', $e);
        }

        $order->update([
            'resellportal_response' => $payload,
        ]);

        $serviceId = ResellPortalProviderId::from($payload['service_id'] ?? null);

        if ($serviceId === null) {
            $order->update([
                'order_status' => EsimOrder::STATUS_FAILED,
                'failure_reason' => 'missing_service_id',
            ]);

            Log::warning('ResellPortal eSIM order missing service_id', ['order_id' => $order->id]);

            throw new EsimPurchaseException('api.esim.provisioning_failed', 502, 'ResellPortal eSIM response missing service_id');
        }

        $details = is_array($payload['esim_details'] ?? null) ? $payload['esim_details'] : [];

        DB::transaction(function () use ($order, $serviceId, $details) {
            $order->update([
                'resellportal_service_id' => $serviceId,
                'order_status' => EsimOrder::STATUS_ACTIVE,
                'failure_reason' => null,
            ]);

            EsimOrderDetail::query()->updateOrCreate(
                ['esim_order_id' => $order->id],
                [
                    'service_id' => $serviceId,
                    'iccid' => $details['iccid'] ?? null,
                    'qr_code_url' => $details['qr_code_url'] ?? null,
                    'activation_url' => $details['activation_url'] ?? null,
                    'esim_status' => $details['esim_status'] ?? EsimOrder::STATUS_ACTIVE,
                ],
            );
        });

        Log::info('eSIM order completed', [
            'order_id' => $order->id,
            'service_id' => $serviceId,
        ]);

        return $order->fresh('detail');
    }

    protected function firstOrCreateLocalOrder(
        User $user,
        EsimPackageData $package,
        EsimPriceQuote $quote,
        PurchaseOffer $offer,
        string $clientId,
        string $idempotencyKey,
    ): EsimOrder {
        return DB::transaction(function () use ($user, $package, $quote, $offer, $clientId, $idempotencyKey) {
            $existing = EsimOrder::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $order = EsimOrder::query()->create([
                'user_id' => $user->id,
                'idempotency_key' => $idempotencyKey,
                'resellportal_client_id' => $clientId,
                'package_code' => $package->packageCode,
                'package_name' => $package->name,
                'package_location' => $package->location,
                'package_data_volume' => $this->volumeFromName($package->name),
                'package_duration' => $this->durationFromName($package->name),
                'provider_cost' => $quote->providerCost->toDecimal(),
                'markup_percentage' => $quote->markupPercentage,
                'markup_amount' => $quote->markupAmount->toDecimal(),
                'customer_price' => $offer->listPrice->toDecimal(),
                'discount_percentage' => $offer->discountPercentage,
                'discount_amount' => $offer->discountAmount->toDecimal(),
                'charged_amount' => $offer->chargedAmount->toDecimal(),
                'currency' => $offer->currency(),
                'payment_status' => EsimOrder::PAYMENT_PENDING,
                'order_status' => EsimOrder::STATUS_PENDING_PAYMENT,
            ]);

            Log::info('eSIM local order created', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'package_code' => $package->packageCode,
                'provider_cost_cents' => $quote->providerCost->cents,
                'markup_percentage' => $quote->markupPercentage,
                'markup_amount_cents' => $quote->markupAmount->cents,
                'customer_price_cents' => $quote->customerPrice->cents,
                'currency' => $quote->currency(),
            ]);

            return $order;
        });
    }

    protected function normalizeIdempotencyKey(?string $key): string
    {
        $key = is_string($key) ? trim($key) : '';

        return $key !== '' ? $key : (string) Str::uuid();
    }

    protected function volumeFromName(string $name): ?string
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*GB/i', $name, $matches)) {
            return $matches[1].'GB';
        }

        return null;
    }

    protected function durationFromName(string $name): ?int
    {
        if (preg_match('/(\d+)\s*Days?/i', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
