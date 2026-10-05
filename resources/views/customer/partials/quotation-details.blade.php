<details class="quote-detail">
    <summary>View details</summary>
    <div class="quote-detail-body">
        <dl>
            <div><dt>Quotation</dt><dd>{{ $quotation->quotation_no }} · R{{ $quotation->revision_no }}</dd></div>
            <div><dt>Related request</dt><dd>{{ $quotationRequest?->request_no ?? 'Not linked' }}</dd></div>
            <div><dt>Customer price</dt><dd>{{ number_format((float) $quotation->amount, 2) }} {{ $quotation->currency }}</dd></div>
            <div><dt>Payment due after invoice</dt><dd>{{ $quotation->payment_terms_days === null ? 'Not specified' : $quotation->payment_terms_days.' days' }}</dd></div>
            <div><dt>Scope and pricing assumptions</dt><dd>{{ (int) data_get($quotationRequest, 'workflow_context.quotation_pricing.quotation_id') === (int) $quotation->id ? (data_get($quotationRequest, 'workflow_context.quotation_pricing.basis') ?: 'Not provided') : 'Not provided' }}</dd></div>
        </dl>
    </div>
</details>
