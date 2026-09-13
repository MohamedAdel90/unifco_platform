<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ApiToken, SystemIntegration, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SystemAdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;
        $users = User::where('tenant_id', $tenantId);
        $activeSessions = Schema::hasTable('user_sessions') ? DB::table('user_sessions')->where('tenant_id', $tenantId)->where('status', 'ACTIVE')->count() : 0;
        $failedLogins = Schema::hasTable('security_events') ? DB::table('security_events')->where('tenant_id', $tenantId)->where('event_type', 'FAILED_LOGIN')->where('occurred_at', '>=', now()->subDay())->count() : 0;
        $alerts = Schema::hasTable('security_events') ? DB::table('security_events')->where('tenant_id', $tenantId)->where('status', 'OPEN')->whereIn('severity', ['WARNING', 'HIGH', 'CRITICAL'])->count() : 0;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->where('failed_at','>=',now()->subDay())->count() : 0;
        $alerts += $failedJobs;
        if (Schema::hasTable('system_integrations')) {
            $integrationTotal = SystemIntegration::where('tenant_id',$tenantId)->where('is_enabled',true)->count();
            $integrations = SystemIntegration::where('tenant_id',$tenantId)->where('is_enabled',true)->whereIn('status',['OPERATIONAL','READY'])->count();
            $integrationIssues = SystemIntegration::where('tenant_id',$tenantId)->where('is_enabled',true)->whereNotIn('status',['OPERATIONAL','READY'])->count();
            $alerts += $integrationIssues;
        } else {
            $integrations = Schema::hasTable('api_tokens') ? ApiToken::where('tenant_id', $tenantId)->whereNull('revoked_at')->count() : 0;
            $integrationTotal = $integrations;
            $integrationIssues = 0;
        }
        $critical = Schema::hasTable('security_events') && DB::table('security_events')->where('tenant_id', $tenantId)->where('status', 'OPEN')->where('severity', 'CRITICAL')->exists();
        $degraded = $failedJobs > 0 || $integrationIssues > 0 || (Schema::hasTable('security_events') && DB::table('security_events')->where('tenant_id', $tenantId)->where('status', 'OPEN')->where('severity', 'HIGH')->exists());
        $status = $critical ? 'CRITICAL' : ($degraded ? 'DEGRADED' : ($alerts > 0 ? 'WARNING' : 'OPERATIONAL'));

        $kpis = [
            'active_users' => (clone $users)->where('status', 'ACTIVE')->count(),
            'customer_users' => (clone $users)->whereNotNull('customer_id')->where('status', 'ACTIVE')->count(),
            'active_sessions' => $activeSessions, 'failed_logins' => $failedLogins,
            'system_alerts' => $alerts, 'integrations' => $integrations, 'integration_total'=>$integrationTotal,
        ];
        $recentUsers = (clone $users)->with(['activeRoles','accessScopes','employee.position'])->latest('last_login_at')->limit(8)->get();
        $events = Schema::hasTable('security_events') ? DB::table('security_events')->where('tenant_id', $tenantId)->latest('occurred_at')->limit(8)->get() : collect();
        $activity = DB::table('audit_logs')->where('tenant_id', $tenantId)->latest('id')->limit(10)->get();
        return view('admin.system.dashboard', compact('kpis', 'status', 'recentUsers', 'events', 'activity', 'failedJobs'));
    }
}
