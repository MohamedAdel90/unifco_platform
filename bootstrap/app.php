<?php

use App\Http\Middleware\{AdminAboutCmsWorkspacePresentation,AdminHomepageCmsIsolationPresentation,AdminHomepageCmsPresentation,AuthenticateApiToken,AuthenticateJwt,BrandingPresentation,CmsArabicSourcePresentationV2,CmsDisplayModePresentation,CustomerPortalDashboardPresentation,CustomerPortalMobileLogoutPresentation,EnsureUserSessionValid,LegacyFormCompatibility,PublicAssetQrInsecureFallback,PublicAssetQrPresentation,PublicContactPresentation,PublicCurrentCustomerContextLayoutPresentation,PublicCurrentCustomerLookupUxPresentation,PublicCurrentCustomerServiceCorePresentation,PublicCurrentCustomerSummaryCardPresentation,PublicCurrentMaintenanceAttachmentsPresentation,PublicCurrentMaintenanceHeaderExactPresentation,PublicCurrentMaintenanceInlineAttachmentPreviewPresentation,PublicCurrentMaintenancePagePresentation,PublicCurrentMaintenancePresentation,PublicCurrentMaintenanceTicketSubmissionFix,PublicCurrentMaintenanceVisitReceptionPresentation,PublicEmergencyBannerCompactPresentation,PublicEmergencyContextBannerPresentation,PublicEmergencyControlsFinalPresentation,PublicEmergencyIssueDetailsPresentation,PublicEmergencyIssueVisualPolishPresentation,PublicEmergencyMaintenancePresentation,PublicFinalUnifiedAttachmentsPresentation,PublicHomeAboutLinkPresentation,PublicHomeAssetStatsPresentation,PublicHomeCmsMediaPresentation,PublicHomeContactDetailsPresentation,PublicHomeEmergencyShortcutPresentation,PublicHomeFinalCompactPresentation,PublicHomeFinalCopyPresentation,PublicHomeFooterPresentation,PublicHomeHeroPresentation,PublicHomeHeroServiceLinkPresentation,PublicHomeLocationPresentation,PublicHomeNavPresentation,PublicHomeOperationsCmsMedia,PublicHomeOperationsPresentation,PublicHomeOperationsTogglePresentation,PublicHomeProcessTitlePresentation,PublicHomeRenderCompatibility,PublicHomeSectionDisplayPresentation,PublicHomeServicesKickerPresentation,PublicHomeSocialLinksPresentation,PublicHomeSpacingPresentation,PublicHtmlResponseContentTypeGuard,PublicNewCustomerEquipmentPresentation,PublicRequestAttachmentsPresentation,PublicRequestBottomTicketPresentation,PublicRequestCompactDesign,PublicRequestHeaderMatch,PublicRequestLegacyChromeCleanup,PublicRequestTicketSystemCompatibility,PublicServiceLinks,PublicUnifiedAssetIssueWorkspacePolishPresentation,PublicUnifiedAssetIssueWorkspacePresentation,PublicUnifiedTicketSubmissionPresentation,RequirePermission,WorkflowRoleHomeRedirect,WorkflowRoleNavigationPresentation};
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
        $middleware->web(append: [PublicHtmlResponseContentTypeGuard::class,PublicHomeEmergencyShortcutPresentation::class,PublicRequestTicketSystemCompatibility::class,PublicUnifiedTicketSubmissionPresentation::class,PublicFinalUnifiedAttachmentsPresentation::class,PublicRequestLegacyChromeCleanup::class,EnsureUserSessionValid::class,PublicHomeAboutLinkPresentation::class,AdminAboutCmsWorkspacePresentation::class,AdminHomepageCmsIsolationPresentation::class,AdminHomepageCmsPresentation::class,CmsDisplayModePresentation::class,CmsArabicSourcePresentationV2::class,PublicHomeContactDetailsPresentation::class,PublicHomeServicesKickerPresentation::class,PublicRequestBottomTicketPresentation::class,PublicEmergencyBannerCompactPresentation::class,WorkflowRoleHomeRedirect::class,WorkflowRoleNavigationPresentation::class,LegacyFormCompatibility::class,CustomerPortalDashboardPresentation::class,CustomerPortalMobileLogoutPresentation::class,PublicHomeRenderCompatibility::class,PublicHomeFinalCompactPresentation::class,PublicHomeFinalCopyPresentation::class,PublicHomeAssetStatsPresentation::class,PublicHomeHeroPresentation::class,PublicHomeHeroServiceLinkPresentation::class,PublicHomeProcessTitlePresentation::class,PublicHomeSocialLinksPresentation::class,PublicHomeSpacingPresentation::class,PublicHomeLocationPresentation::class,PublicHomeFooterPresentation::class,PublicHomeOperationsPresentation::class,PublicHomeNavPresentation::class,PublicHomeCmsMediaPresentation::class,PublicHomeOperationsCmsMedia::class,PublicHomeSectionDisplayPresentation::class,PublicHomeOperationsTogglePresentation::class,BrandingPresentation::class,PublicCurrentCustomerSummaryCardPresentation::class,PublicCurrentCustomerContextLayoutPresentation::class,PublicCurrentCustomerServiceCorePresentation::class,PublicCurrentCustomerLookupUxPresentation::class,PublicCurrentMaintenancePagePresentation::class,PublicCurrentMaintenanceHeaderExactPresentation::class,PublicNewCustomerEquipmentPresentation::class,PublicEmergencyMaintenancePresentation::class,PublicCurrentMaintenancePresentation::class,PublicEmergencyControlsFinalPresentation::class,PublicRequestHeaderMatch::class,PublicRequestAttachmentsPresentation::class,PublicAssetQrInsecureFallback::class,PublicAssetQrPresentation::class,PublicRequestCompactDesign::class,PublicServiceLinks::class,PublicContactPresentation::class,PublicCurrentMaintenanceVisitReceptionPresentation::class,PublicCurrentMaintenanceTicketSubmissionFix::class,PublicEmergencyIssueDetailsPresentation::class,PublicEmergencyIssueVisualPolishPresentation::class,PublicUnifiedAssetIssueWorkspacePolishPresentation::class,PublicUnifiedAssetIssueWorkspacePresentation::class,PublicEmergencyContextBannerPresentation::class]);
        $middleware->validateCsrfTokens(except: ['login','service-requests']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();