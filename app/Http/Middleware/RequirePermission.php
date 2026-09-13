<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function __construct(private AuthorizationService $authorization) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        // Compatibility bridge: asset creation and registry now live exclusively in
        // Professional Asset Master. Old EAM bookmarks/navigation must never send
        // governed asset roles into the legacy eam.asset.manage permission wall.
        if (
            in_array($request->path(), ['eam/assets', 'eam/assets/create'], true)
            && in_array($user->role, ['ADMIN', 'MAINTENANCE_ENGINEER', 'MAINTENANCE_MANAGER', 'PROJECT_MANAGER'], true)
        ) {
            return redirect()->route('asset-master.index');
        }

        $resource=collect($request->route()?->parameters()??[])->first(fn($parameter)=>$parameter instanceof Model);
        $this->authorization->authorize($user, $permission, $resource ?: null);
        return $next($request);
    }
}
