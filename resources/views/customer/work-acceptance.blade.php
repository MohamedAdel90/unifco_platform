@include('customer.partials.portal-shell-open',[
    'customer'=>$customer,
    'activeSection'=>'actions',
    'pageTitle'=>'اعتماد الأعمال والتقييم · Work Acceptance',
    'pageDescription'=>'مراجعة الأعمال المكتملة وتقييم خدمات الشركة بالكامل',
])

<div class="portal-page-head">
    <div><h2>اعتماد الأعمال المكتملة</h2><p>جميع أوامر العمل المنجزة الخاصة بالشركة والقرارات السابقة وطلبات تقييم الرضا.</p></div>
    <span class="portal-pill {{ $pending->count() ? 'red' : 'green' }}">{{ $pending->count() }} بانتظار القرار</span>
</div>

<section class="portal-card portal-panel">
    <h3>بانتظار قرار العميل · Awaiting Acceptance</h3>
    <div class="accept-grid">
        @forelse($pending as $workOrder)
            <article class="accept-item">
                <div class="accept-head"><a href="{{ route('customer.work-orders.show',$workOrder) }}"><strong>{{ $workOrder->work_order_no }}</strong></a><span class="portal-pill amber">{{ str_replace('_',' ',$workOrder->maintenance_type) }}</span></div>
                <div class="accept-meta">اكتمل في {{ $workOrder->completed_at?->format('Y-m-d H:i') ?: '—' }}</div>
                <form method="POST" action="{{ route('customer.work-acceptance.decide',$workOrder) }}">@csrf
                    <textarea name="notes" maxlength="2000" placeholder="ملاحظات القرار أو تفاصيل العمل المطلوب مراجعته"></textarea>
                    <div class="accept-actions"><button class="portal-btn green" name="decision" value="ACCEPT">اعتماد الأعمال</button><button class="portal-btn red" name="decision" value="REJECT">طلب إعادة العمل</button></div>
                </form>
            </article>
        @empty
            <div class="portal-empty"><strong>لا توجد أعمال بانتظار الاعتماد</strong>كل الأعمال المكتملة تمت مراجعتها حاليًا.</div>
        @endforelse
    </div>
</section>

<section class="portal-card portal-panel section-gap">
    <h3>قياس رضا العميل · Customer Satisfaction</h3>
    <div class="accept-grid">
        @forelse($satisfactionRequests as $request)
            <article class="accept-item">
                <a href="{{ route('customer.service-requests.show',$request) }}"><strong>{{ $request->request_no }}</strong></a>
                <p>تم إغلاق العمل تشغيليًا. قيّم الخدمة لإكمال دورة الطلب.</p>
                <form method="POST" action="{{ route('customer.requests.satisfaction',$request) }}">@csrf
                    <div class="score-grid"><label>التقييم<select name="rating" required><option value="">اختر</option>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }} / 5</option>@endfor</select></label><label>NPS<select name="nps"><option value="">اختياري</option>@for($i=10;$i>=0;$i--)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></label></div>
                    <textarea name="comment" maxlength="2000" placeholder="ملاحظاتك على الخدمة"></textarea>
                    <button class="portal-btn">إرسال التقييم وإغلاق الطلب</button>
                </form>
            </article>
        @empty
            <div class="portal-empty"><strong>لا توجد تقييمات مطلوبة</strong>لا توجد طلبات بانتظار قياس الرضا حاليًا.</div>
        @endforelse
    </div>
</section>

<section class="portal-card portal-panel section-gap">
    <h3>سجل قرارات الشركة · Company Decision History</h3>
    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>أمر العمل</th><th>القرار</th><th>التاريخ</th><th>الملاحظات</th></tr></thead><tbody>
        @forelse($history as $workOrder)<tr><td><a href="{{ route('customer.work-orders.show',$workOrder) }}"><strong>{{ $workOrder->work_order_no }}</strong></a></td><td><span class="portal-pill {{ $workOrder->customer_accepted_at ? 'green' : 'red' }}">{{ $workOrder->customer_accepted_at ? 'معتمد' : 'إعادة عمل' }}</span></td><td>{{ ($workOrder->customer_accepted_at ?: $workOrder->customer_rejected_at)?->format('Y-m-d H:i') }}</td><td>{{ $workOrder->customer_acceptance_notes ?: '—' }}</td></tr>
        @empty<tr><td colspan="4" class="portal-empty">لا يوجد سجل قرارات بعد.</td></tr>@endforelse
    </tbody></table></div>
</section>

@push('late-styles')<style>
.accept-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.accept-item{border:1px solid #e5ebf2;border-radius:11px;padding:14px;min-width:0}.accept-head,.accept-actions{display:flex;align-items:center;justify-content:space-between;gap:8px}.accept-meta,.accept-item p{color:var(--muted);font-size:9px;margin:7px 0 11px}.accept-item textarea,.accept-item select{width:100%;border:1px solid #d9e1eb;border-radius:8px;padding:9px;background:#fff;font-size:10px}.accept-item textarea{min-height:74px;resize:vertical;margin-bottom:9px}.score-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}.score-grid label{font-size:9px;font-weight:700}.section-gap{margin-top:12px}@media(max-width:800px){.accept-grid{grid-template-columns:1fr}}
</style>@endpush
@include('customer.partials.portal-shell-close')
