<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestTicketSystemCompatibility
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('public.request.store')) {
            $subtype = strtoupper(trim((string) $request->input('request_subtype', '')));

            $aliases = [
                'SPARE_PARTS' => 'SPARE_PARTS_QUOTE',
                'MAINTENANCE_CONTRACT' => 'MAINTENANCE_CONTRACT_QUOTE',
                'EMERGENCY_MAINTENANCE' => 'URGENT_MAINTENANCE',
                // Technical visit is a quotation request. The current ticket engine uses
                // the UNQ quotation sequence for non-contract quotation requests.
                'TECHNICAL_VISIT' => 'SPARE_PARTS_QUOTE',
            ];

            if (isset($aliases[$subtype])) {
                $request->merge(['request_subtype' => $aliases[$subtype]]);
            }

            $normalized = strtoupper(trim((string) $request->input('request_subtype', '')));
            $intent = match ($normalized) {
                'SPARE_PARTS_QUOTE', 'MAINTENANCE_CONTRACT_QUOTE' => 'QUOTATION',
                'ROUTINE_MAINTENANCE', 'URGENT_MAINTENANCE' => 'SERVICE_REQUEST',
                'TECHNICAL_CONSULTATION' => 'CONSULTATION',
                default => strtoupper((string) $request->input('request_intent', '')),
            };

            if ($intent !== '') {
                $request->merge(['request_intent' => $intent]);
            }

            if ($subtype === 'TECHNICAL_VISIT' && ! $request->filled('service_other')) {
                $request->merge(['service_other' => 'زيارة فنية']);
            }

            // Keep ticket issuance resilient across all unified request forms. The visit
            // UI may be rendered asynchronously; only provide safe defaults when those
            // fields did not reach the POST at all.
            if (! $request->filled('requested_date')) {
                $request->merge(['requested_date' => today()->toDateString()]);
            }
            if (! $request->filled('requested_time')) {
                $request->merge(['requested_time' => '09:00']);
            }
        }

        return $next($request);
    }
}
