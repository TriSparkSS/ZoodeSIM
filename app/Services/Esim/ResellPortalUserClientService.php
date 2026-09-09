<?php

namespace App\Services\Esim;

use App\Exceptions\EsimPurchaseException;
use App\Exceptions\ResellPortalException;
use App\Models\User;
use App\Services\Esim\Contracts\ResellPortalUserClientServiceInterface;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellPortalUserClientService implements ResellPortalUserClientServiceInterface
{
    public function __construct(
        protected ResellPortalClientInterface $client,
    ) {}

    public function resolve(User $user): int
    {
        if ($user->resellportal_client_id) {
            Log::info('ResellPortal client reused', [
                'user_id' => $user->id,
                'client_id' => $user->resellportal_client_id,
            ]);

            return (int) $user->resellportal_client_id;
        }

        return (int) DB::transaction(function () use ($user) {
            $fresh = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($fresh->resellportal_client_id) {
                Log::info('ResellPortal client reused', [
                    'user_id' => $fresh->id,
                    'client_id' => $fresh->resellportal_client_id,
                ]);

                return (int) $fresh->resellportal_client_id;
            }

            $payload = $this->client->createClient(
                $fresh->name,
                $fresh->email,
                $fresh->phone,
            );

            $clientId = (int) ($payload['client_id'] ?? 0);

            if ($clientId < 1) {
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

    public function tryEnsure(User $user): ?int
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
