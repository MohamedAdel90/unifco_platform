<?php

use App\Http\Middleware\{AuthenticateApiToken,AuthenticateJwt,BrandingPresentation,CmsArabicSourcePresentation,CmsDisplayModePresentation,CustomerPortalDashboardPresentation,CustomerPortalMobileLogoutPresentation,EnsureUserSessionValid,LegacyFormCompatibility,PublicAssetQrInsecureFallback,PublicAssetQrPresentation,PublicContactPresentation,PublicCurrentCustomerLookupUxPresentation,PublicCurrentMaintenanceAttachmentsPresentation,PublicCurrentMaintenanceHeaderExactPresentation,PublicCurrentMaintenancePagePresentation,PublicCurrentMaintenancePresentation,PublicEmergencyBannerCompactPresentation,PublicEmergencyMaintenancePresentation,PublicHomeAboutProfilePresentation,PublicHomeAssetStatsPresentation,PublicHomeCmsMediaPresentation,PublicHomeContactDetailsPresentation,PublicHomeFinalCompactPresentation,PublicHomeFinalCopyPresentation,PublicHomeFooterPresentation,PublicHomeHeroPresentation,PublicHomeHeroServiceLinkPresentation,PublicHomeLocationPresentation,PublicHomeNavPresentation,PublicHomeOperationsCmsMedia,PublicHomeOperationsPresentation,PublicHomeOperationsTogglePresentation,PublicHomeProcessTitlePresentation,PublicHomeRenderCompatibility,PublicHomeSectionDisplayPresentation,PublicHomeServicesKickerPresentation,PublicHomeSocialLinksPresentation,PublicHomeSpacingPresentation,PublicNewCustomerEquipmentPresentation,PublicRequestAttachmentsPresentation,PublicRequestBottomTicketPresentation,PublicRequestCompactDesign,PublicRequestHeaderMatch,PublicServiceLinks,RequirePermission,WorkflowRoleHomeRedirect,WorkflowRoleNavigationPresentation};
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/public.php',
            __DIR__.'/../routes/current-customer-maintenance.php',
            __DIR__.'/../routes/public-home-compat.php',
            __DIR__.'/../routes/customer-phase2.php',
            __DIR__.'/../routes/customer-acquisition.php',
            __DIR__.'/../routes/asset-master.php',
            __DIR__.'/../routes/public-asset-qr.php',
            __DIR__.'/../routes/services.php',
            __DIR__.'/../routes/brand.php',
            __DIR__.'/../routes/field.php',
            __DIR__.'/../routes/reporting.php',
            __DIR__.'/../routes/parts.php',
            __DIR__.'/../routes/wave9.php',
            __DIR__.'/../routes/wave10.php',
            __DIR__.'/../routes/wave11.php',
            __DIR__.'/../routes/wave12.php',
            __DIR__.'/../routes/wave13.php',
            __DIR__.'/../routes/wave14.php',
            __DIR__.'/../routes/wave15.php',
            __DIR__.'/../routes/wave16.php',
            __DIR__.'/../routes/wave17.php',
            __DIR__.'/../routes/wave18.php',
            __DIR__.'/../routes/navigation.php',
            __DIR__.'/../routes/cms-portals.php',
        ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['permission'=>RequirePermission::class,'api.token'=>AuthenticateApiToken::class,'jwt'=>AuthenticateJwt::class]);
        $middleware->web(append: [EnsureUserSessionValid::class,CmsDisplayModePresentation::class,CmsArabicSourcePresentation::class,PublicHomeContactDetailsPresentation::class,PublicHomeServicesKickerPresentation::class,PublicRequestBottomTicketPresentation::class,PublicEmergencyBannerCompactPresentation::class,WorkflowRoleHomeRedirect::class,WorkflowRoleNavigationPresentation::class,LegacyFormCompatibility::class,CustomerPortalDashboardPresentation::class,CustomerPortalMobileLogoutPresentation::class,PublicHomeRenderCompatibility::class,PublicHomeFinalCompactPresentation::class,PublicHomeFinalCopyPresentation::class,PublicHomeAssetStatsPresentation::class,PublicHomeHeroPresentation::class,PublicHomeHeroServiceLinkPresentation::class,PublicHomeProcessTitlePresentation::class,PublicHomeSocialLinksPresentation::class,PublicHomeSpacingPresentation::class,PublicHomeLocationPresentation::class,PublicHomeFooterPresentation::class,PublicHomeOperationsPresentation::class,PublicHomeNavPresentation::class,PublicHomeCmsMediaPresentation::class,PublicHomeOperationsCmsMedia::class,PublicHomeSectionDisplayPresentation::class,PublicHomeOperationsTogglePresentation::class,PublicHomeAboutProfilePresentation::class,BrandingPresentation::class,PublicCurrentCustomerLookupUxPresentation::class,PublicCurrentMaintenancePagePresentation::class,PublicCurrentMaintenanceHeaderExactPresentation::class,PublicNewCustomerEquipmentPresentation::class,PublicEmergencyMaintenancePresentation::class,PublicCurrentMaintenancePresentation::class,PublicCurrentMaintenanceAttachmentsPresentation::class,PublicRequestHeaderMatch::class,PublicRequestAttachmentsPresentation::class,PublicAssetQrInsecureFallback::class,PublicAssetQrPresentation::class,PublicRequestCompactDesign::class,PublicServiceLinks::class,PublicContactPresentation::class]);
        $middleware->validateCsrfTokens(except: ['login','service-requests']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();
