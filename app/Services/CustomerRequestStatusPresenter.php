<?php

namespace App\Services;

use App\Models\ServiceRequest;

class CustomerRequestStatusPresenter
{
    public function present(ServiceRequest $request): array
    {
        $stage = strtoupper((string) $request->workflow_stage);

        return match ($stage) {
            'CUSTOMER_APPROVAL', 'CUSTOMER_ACCEPTANCE', 'CUSTOMER_DELIVERY' => [
                'code' => 'ACTION_REQUIRED',
                'label' => 'Action Required · إجراء مطلوب',
                'description' => 'Your review or approval is required to continue this request.',
            ],
            'EMERGENCY_DISPATCH', 'SCHEDULING' => [
                'code' => 'SCHEDULING',
                'label' => 'Scheduling / Dispatch · الجدولة والتوجيه',
                'description' => 'UNIFCO is arranging the service visit or dispatch.',
            ],
            'IN_PROGRESS' => [
                'code' => 'IN_PROGRESS',
                'label' => 'In Progress · جاري التنفيذ',
                'description' => 'The service team is working on your request.',
            ],
            'FINANCE_REVIEW', 'CLOSURE' => [
                'code' => 'FINALIZING',
                'label' => 'Finalizing · جاري الإنهاء',
                'description' => 'The request is being finalized after service completion.',
            ],
            'CSAT' => [
                'code' => 'FEEDBACK',
                'label' => 'Feedback · تقييم الخدمة',
                'description' => 'The service is complete and ready for your feedback.',
            ],
            'COMPLETED' => [
                'code' => 'COMPLETED',
                'label' => 'Completed · مكتمل',
                'description' => 'This request has been completed.',
            ],
            'REJECTED', 'CANCELLED' => [
                'code' => $stage,
                'label' => $stage === 'REJECTED' ? 'Not Proceeding · لم يتم المتابعة' : 'Cancelled · ملغي',
                'description' => $stage === 'REJECTED' ? 'This request will not proceed in its current form.' : 'This request has been cancelled.',
            ],
            default => [
                'code' => 'UNDER_REVIEW',
                'label' => 'Under Review · قيد المراجعة',
                'description' => 'UNIFCO is reviewing and processing your request.',
            ],
        };
    }
}
