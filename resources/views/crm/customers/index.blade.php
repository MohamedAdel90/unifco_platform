@extends('layouts.app')
@section('title','العملاء · UNIFCO')
@section('heading','العملاء / Customers')
@section('content')
<style>
.customers-page{direction:rtl}.customers-hero{background:linear-gradient(135deg,#071f4d,#123d72);border-radius:18px;padding:24px;color:#fff;display:flex;justify-content:space-between;gap:20px;align-items:center;position:relative;overflow:hidden}.customers-hero:after{content:"";position:absolute;width:320px;height:320px;border-radius:50%;background:#ffffff08;left:-90px;top:-160px}.customers-hero>div{position:relative;z-index:2}.eyebrow{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:#ffffff16;font-size:9px;font-weight:800}.customers-hero h2{margin:9px 0 6px;font-size:24px}.customers-hero p{margin:0;max-width:700px;color:#d3deec;font-size:11px;line-height:1.8}.hero-actions{display:flex;gap:8px;flex-wrap:wrap;position:relative;z-index:2}.hero-actions .btn{background:#fff;color:#071f4d}.hero-actions .btn.primary{background:#ce122d;color:#fff}.section-title{display:flex;justify-content:space-between;gap:12px;align-items:end;margin:20px 0 10px}.section-title h3{margin:0;color:#071f4d;font-size:15px}.section-title small{color:#758399;font-size:9px}.customer-card{padding:0;overflow:hidden}.table-wrap{overflow:auto}.customer-table{display:table;margin:0;min-width:980px}.customer-table th{background:#f6f8fb;color:#68758a;font-size:9px;font-weight:800;border-bottom:1px solid #e4e9f0}.customer-table td{font-size:11px;vertical-align:middle}.customer-code{font-weight:900;color:#071f4d}.customer-name{font-weight:800;color:#172033}.customer-meta{color:#68758a;font-size:9px;line-height:1.6}.status-pill{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:8px;font-weight:900;background:#eef3fa;color:#31527c}.status-pill.ACTIVE,.status-pill.COMPLETED{background:#e9f7ef;color:#176a43}.status-pill.BLOCKED{background:#fdebed;color:#b42239}.status-pill.ONBOARDING,.status-pill.DRAFT{background:#fff1df;color:#9b5e00}.row-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.row-actions form{margin:0}.row-actions .btn{padding:7px 10px;font-size:9px}.portal-btn{background:#071f4d!important;color:#fff!important}.block-btn{background:#ce122d!important;color:#fff!important}.empty-state{padding:34px;text-align:center;color:#758399}.empty-state b{display:block;color:#071f4d;font-size:14px;margin-bottom:5px}.customer-foot{display:grid;grid-template-columns:1fr auto;gap:14px;align-items:center;margin-top:12px;padding:14px 16px;background:#fff;border:1px solid #e3e8ef;border-radius:12px}.customer-foot b{display:block;color:#071f4d;font-size:11px}.customer-foot small{color:#758399;font-size:9px}.customer-foot .btn{background:#ce122d}.pagination-wrap{direction:ltr;margin-top:14px}@media(max-width:760px){.customers-hero{align-items:flex-start;flex-direction:column}.customers-hero h2{font-size:20px}.section-title{align-items:flex-start;flex-direction:column}.customer-foot{grid-template-columns:1fr}.customer-foot .btn{width:100%;text-align:center}}
</style>
<div class="customers-page">
<section class="customers-hero">
    <div>
        <span class="eyebrow">عملاؤنا · UNIFCO CUSTOMERS</span>
        <h2>شراكات طويلة الأمد تبدأ من إدارة أفضل</h2>
        <p>مركز موحد لإدارة بيانات العملاء والتسجيل والعقود والوصول إلى مركز العميل، بنفس الهوية البصرية المعتمدة في الصفحة الرئيسية.</p>
    </div>
    <div class="hero-actions">
        <a class="btn primary" href="{{ route('crm.customers.create') }}">+ عميل جديد</a>
        <a class="btn" href="{{ route('dashboard') }}">العودة للرئيسية</a>
    </div>
</section>

@if(session('status'))<p class="notice" style="margin-top:14px">{{ session('status') }}</p>@endif

<div class="section-title">
    <div><h3>العملاء · Customer Portfolio</h3><small>إدارة التسجيل والبيانات والحالة التشغيلية لكل عميل</small></div>
    <small>{{ $customers->total() }} عميل</small>
</div>

<div class="card customer-card">
    <div class="table-wrap">
        <table class="customer-table">
            <thead><tr><th>الكود</th><th>العميل</th><th>القطاع / المدينة</th><th>البريد الإلكتروني</th><th>التسجيل</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
            <tbody>
            @forelse($customers as $c)
                <tr>
                    <td><span class="customer-code">{{ $c->customer_code }}</span></td>
                    <td><span class="customer-name">{{ $c->name }}</span></td>
                    <td><div class="customer-meta"><strong>{{ $c->industry ?: '—' }}</strong><br>{{ $c->city ?: '—' }}</div></td>
                    <td>{{ $c->email ?: '—' }}</td>
                    <td><span class="status-pill {{ strtoupper($c->onboarding_status ?: 'DRAFT') }}">{{ $c->onboarding_status ?: 'DRAFT' }}</span></td>
                    <td><span class="status-pill {{ strtoupper($c->status) }}">{{ $c->status }}</span></td>
                    <td><div class="row-actions">
                        <a class="btn portal-btn" href="{{ route('crm.customers.portal',$c) }}">مركز العميل</a>
                        <a class="btn secondary" href="{{ route('crm.customers.edit',$c) }}">تعديل</a>
                        @if($c->status==='ACTIVE')<form method="POST" action="{{ route('crm.customers.block',$c) }}">@csrf<button class="btn block-btn" type="submit">حظر</button></form>@endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><b>لا يوجد عملاء حتى الآن</b><span>ابدأ بإضافة أول عميل وإكمال خطوات التسجيل.</span></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $customers->links() }}</div>

<div class="customer-foot">
    <div><b>عميل جديد / New Customer</b><small>أضف بيانات العميل، ثم أكمل تسجيل العميل / Customer Onboarding من مركز العميل.</small></div>
    <a class="btn" href="{{ route('crm.customers.create') }}">إضافة عميل</a>
</div>
</div>
@endsection
