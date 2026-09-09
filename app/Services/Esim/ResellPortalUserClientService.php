<?php

namespace App\Services\Esim;

use App\Exceptions\EsimPurchaseException;
use App\Exceptions\ResellPortalException;
use App\Models\User;
use App\Services\Esim\Contracts\ResellPortalUserClientServiceInterface;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use App\Support\ResellPortalProviderId;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellPortalUserClientService implements ResellPortalUserClientServiceInterface
{
    public function __construct(
        protected ResellPortalClientInterface $client,
    ) {}

    public function resolve(User $user): string
    {
        $existing = ResellPortalProviderId::from($user->resellportal_client_id);

        if ($existing !== null) {
            Log::info('ResellPortal client reused', [
                'user_id' => $user->id,
                'client_id' => $existing,
            ]);

            return $existing;
        }

        return (string) DB::transaction(function () use ($user) {
            $fresh = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = ResellPortalProviderId::from($fresh->resellportal_client_id);

            if ($existing !== null) {
                Log::info('ResellPortal client reused', [
                    'user_id' => $fresh->id,
                    'client_id' => $existing,
                ]);

                return $existing;
            }

            $payload = $this->client->createClient(
                $fresh->name,
                $fresh->email,
                $fresh->phone,
            );

            $clientId = ResellPortalProviderId::from($payload['client_id'] ?? null);

            if ($clientId === null) {
                throw new EsimPurchaseException('api.esim.purchase_failed', 503, 'ResellPortal client_id missing');
            }

            $fresh->forceFill(['resellportal_client_id' => $clientId])->save();

            Log::info('ResellPortal client created', [
                'user_id' => $fresh->id,
                'client_id' => $clientId,
            ]);

            return $clientId;
        });
    }

    public function tryEnsure(User $user): ?string
    {
        try {
            return $this->resolve($user);
        } catch (ResellPortalException|EsimPurchaseException|QueryException $e) {
            Log::warning('ResellPortal client not provisioned during registration', [
                'user_id' => $user->id,
                'reason' => $e instanceof ResellPortalException ? $e->reason : $e::class,
            ]);

            return null;
        }
    }
}
