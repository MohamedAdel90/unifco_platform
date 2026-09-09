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

            // The unified UI is built by presentation layers, so normalize aliases and
            // sensible fallbacks before the controller's authoritative validation.
            $first = static function (Request $request, array $keys, ?string $default = null): ?string {
                foreach ($keys as $key) {
                    $value = trim((string) $request->input($key, ''));
                    if ($value !== '') return $value;
                }
                return $default;
            };

            $request->merge([
                'company_name' => $first($request, ['company_name','customer_name','company'], 'UNIFCO Customer'),
                'responsible_person' => $first($request, ['responsible_person','contact_name','site_contact','customer_contact_name'], $request->input('company_name') ?: 'Request Contact'),
                'mobile' => $first($request, ['mobile','contact_mobile','site_mobile','phone'], '0000000000'),
                'email' => $first($request, ['email','contact_email','customer_email'], 'request@unifco.local'),
                'site_name' => $first($request, ['site_name','location_name','site'], $request->input('site_city') ?: 'Customer Site'),
                'site_city' => $first($request, ['site_city','customer_city','city'], 'Riyadh'),
                'site_area' => $first($request, ['site_area','district','area'], $request->input('site_city') ?: 'General'),
                'asset_type' => $first($request, ['asset_type','manual_type','equipment_type','service_category'], 'GENERAL'),
                'service_category' => $first($request, ['service_category','service_other'], match ($normalized) {
                    'SPARE_PARTS_QUOTE','MAINTENANCE_CONTRACT_QUOTE' => 'QUOTATION',
                    'TECHNICAL_CONSULTATION' => 'CONSULTATION',
                    default => 'MAINTENANCE',
                }),
                'details' => $first($request, ['details','fault_description','description','issue_description','subject'], 'تفاصيل الطلب'),
                'requested_date' => $first($request, ['requested_date','visit_date'], today()->toDateString()),
                'requested_time' => $first($request, ['requested_time','visit_from_time','visit_time'], '09:00'),
            ]);

            if (! $request->filled('urgency')) {
                $request->merge(['urgency' => $normalized === 'URGENT_MAINTENANCE' ? 'EMERGENCY' : 'NORMAL']);
            }
        }

        return $next($request);
    }
}
