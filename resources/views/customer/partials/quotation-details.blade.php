@php
    $pricing = data_get($quotationRequest, 'workflow_context.quotation_pricing');
    $matchedPricing = (int) data_get($pricing, 'quotation_id') === (int) $quotation->id;
    $customerScope = $matchedPricing ? trim((string) data_get($pricing, 'customer_scope')) : '';
@endphp
<details class="quote-detail">
    <summary>View details</summary>
    <div class="quote-detail-body">
        <dl>
            <div><dt>Quotation</dt><dd>{{ $quotation->quotation_no }} · R{{ $quotation->revision_no }}</dd></div>
            <div><dt>Related request</dt><dd>{{ $quotationRequest?->request_no ?? 'Not linked' }}</dd></div>
            <div><dt>Customer price</dt><dd>{{ number_format((float) $quotation->amount, 2) }} {{ $quotation->currency }}</dd></div>
            <div><dt>Payment due after invoice</dt><dd>{{ $quotation->payment_terms_days === null ? 'Not specified' : $quotation->payment_terms_days.' days' }}</dd></div>
            <div><dt>Customer scope</dt><dd>{{ $customerScope !== '' ? $customerScope : 'Scope is not confirmed. Request a revision before approval.' }}</dd></div>
        </dl>
    </div>
</details>
