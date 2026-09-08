<?php

namespace App\Providers;

use App\Services\Esim\Contracts\EsimOrderServiceInterface;
use App\Services\Esim\Contracts\EsimPackageServiceInterface;
use App\Services\Esim\Contracts\EsimPaymentGatewayInterface;
use App\Services\Esim\Contracts\EsimPricingServiceInterface;
use App\Services\Esim\EsimOrderService;
use App\Services\Esim\EsimPackageService;
use App\Services\Esim\InternalSettlementPaymentGateway;
use App\Services\Fraud\Contracts\DeviceFraudServiceInterface;
use App\Services\Fraud\DeviceFraudService;
use App\Services\Locale\LocaleManager;
use App\Services\Logging\ApiLoggerService;
use App\Services\Logging\Contracts\ApiLoggerServiceInterface;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Partner\Contracts\PartnerBalanceAdjustmentServiceInterface;
use App\Services\Partner\Contracts\PartnerLedgerServiceInterface;
use App\Services\Partner\Contracts\PayoutIdentityServiceInterface;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use App\Services\Partner\PartnerBalanceAdjustmentService;
use App\Services\Partner\PartnerLedgerService;
use App\Services\Partner\PayoutIdentityService;
use App\Services\Partner\WithdrawalService;
use App\Services\Pricing\Contracts\PricingServiceInterface;
use App\Services\Pricing\PricingService;
use App\Services\Promo\Contracts\PromoAuditLoggerInterface;
use App\Services\Promo\Contracts\PromoValidationServiceInterface;
use App\Services\Promo\PromoAuditLogger;
use App\Services\Promo\PromoValidationService;
use App\Services\Referral\Contracts\PurchaseOfferServiceInterface;
use App\Services\Referral\Contracts\PurchaseSettlementServiceInterface;
use App\Services\Referral\Contracts\ReferralMilestoneServiceInterface;
use App\Services\Referral\PurchaseOfferService;
use App\Services\Referral\PurchaseSettlementService;
use App\Services\Referral\ReferralMilestoneService;
use App\Services\Referral\ReferralProgramSettings;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use App\Services\ResellPortal\ResellPortalClient;
use App\Services\User\Contracts\UserAdminServiceInterface;
use App\Services\User\UserAdminService;
use App\Services\Wallet\Contracts\TransactionIdGeneratorInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Services\Wallet\TransactionIdGenerator;
use App\Services\Wallet\WalletLedgerService;
use App\Support\ApiLogContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocaleManager::class);
        $this->app->singleton(ApiLogContext::class);
        $this->app->singleton(ApiLoggerServiceInterface::class, ApiLoggerService::class);
        $this->app->singleton(ResellPortalClientInterface::class, ResellPortalClient::class);
        $this->app->singleton(EsimPackageServiceInterface::class, EsimPackageService::class);
        $this->app->singleton(PricingService::class);
        $this->app->singleton(PricingServiceInterface::class, PricingService::class);
        $this->app->singleton(EsimPricingServiceInterface::class, PricingService::class);
        $this->app->singleton(EsimPaymentGatewayInterface::class, InternalSettlementPaymentGateway::class);
        $this->app->singleton(DeviceFraudServiceInterface::class, DeviceFraudService::class);
        $this->app->singleton(NotificationDispatcherInterface::class, NotificationDispatcher::class);
        $this->app->singleton(PromoAuditLoggerInterface::class, PromoAuditLogger::class);
        $this->app->singleton(PromoValidationServiceInterface::class, PromoValidationService::class);
        $this->app->singleton(PayoutIdentityServiceInterface::class, PayoutIdentityService::class);
        $this->app->singleton(ReferralProgramSettings::class);
        $this->app->singleton(PurchaseOfferServiceInterface::class, PurchaseOfferService::class);
        $this->app->singleton(PurchaseSettlementServiceInterface::class, PurchaseSettlementService::class);
        $this->app->singleton(ReferralMilestoneServiceInterface::class, ReferralMilestoneService::class);
        $this->app->singleton(EsimOrderServiceInterface::class, EsimOrderService::class);
        $this->app->singleton(TransactionIdGeneratorInterface::class, TransactionIdGenerator::class);
        $this->app->singleton(WalletLedgerServiceInterface::class, WalletLedgerService::class);
        $this->app->singleton(PartnerLedgerServiceInterface::class, PartnerLedgerService::class);
        $this->app->singleton(PartnerBalanceAdjustmentServiceInterface::class, PartnerBalanceAdjustmentService::class);
        $this->app->singleton(WithdrawalServiceInterface::class, WithdrawalService::class);
        $this->app->singleton(UserAdminServiceInterface::class, UserAdminService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
