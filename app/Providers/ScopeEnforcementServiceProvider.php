<?php

namespace App\Providers;

use App\Models\{
    AccessScope,
    ApiToken,
    ApprovalAuthority,
    BrandingSetting,
    EmailTemplate,
    HomepageClient,
    HomepageProject,
    HomepageSection,
    JobPosition,
    MasterDataEntry,
    Organization,
    Permission,
    PlatformNotification,
    ReportSubscription,
    Role,
    SecurityEvent,
    SystemIntegration,
    Tenant,
    User,
    UserInvitation,
    UserSession
};
use App\Scopes\RuntimeDataScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Registers a single data-layer enforcement boundary for operational models.
 *
 * This closes the historical controller-by-controller gap: list, detail,
 * pagination, KPI/count, export and report queries that use Eloquent all pass
 * through the same tenant + assigned-scope boundary automatically.
 */
class ScopeEnforcementServiceProvider extends ServiceProvider
{
    private const EXEMPT_MODELS = [
        AccessScope::class,
        ApiToken::class,
        ApprovalAuthority::class,
        BrandingSetting::class,
        EmailTemplate::class,
        HomepageClient::class,
        HomepageProject::class,
        HomepageSection::class,
        JobPosition::class,
        MasterDataEntry::class,
        Organization::class,
        Permission::class,
        PlatformNotification::class,
        ReportSubscription::class,
        Role::class,
        SecurityEvent::class,
        SystemIntegration::class,
        Tenant::class,
        User::class,
        UserInvitation::class,
        UserSession::class,
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        $scope = new RuntimeDataScope();

        foreach (File::files(app_path('Models')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();
            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            if (in_array($class, self::EXEMPT_MODELS, true)) {
                continue;
            }

            $class::addGlobalScope('unifco_runtime_data_scope', $scope);
        }
    }
}
