@extends('layouts.app')
@section('title','المشاريع · UNIFCO')
@section('heading','المشاريع / Projects')
@section('content')
<style>
.projects-page{direction:rtl}.projects-hero{background:linear-gradient(135deg,#071f4d,#123d72);border-radius:18px;padding:24px;color:#fff;display:flex;justify-content:space-between;gap:20px;align-items:center;position:relative;overflow:hidden}.projects-hero:after{content:"";position:absolute;width:300px;height:300px;border-radius:50%;background:#ffffff08;left:-90px;top:-150px}.projects-hero>div{position:relative;z-index:2}.eyebrow{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:#ffffff16;font-size:9px;font-weight:800}.projects-hero h2{margin:9px 0 6px;font-size:24px}.projects-hero p{margin:0;max-width:680px;color:#d3deec;font-size:11px;line-height:1.8}.hero-actions{display:flex;gap:8px;flex-wrap:wrap;position:relative;z-index:2}.hero-actions .btn{background:#fff;color:#071f4d}.hero-actions .btn.primary{background:#ce122d;color:#fff}.section-title{display:flex;justify-content:space-between;gap:12px;align-items:end;margin:20px 0 10px}.section-title h3{margin:0;color:#071f4d;font-size:15px}.section-title small{color:#758399;font-size:9px}.portfolio-card{padding:0;overflow:hidden}.table-wrap{overflow:auto}.portfolio-table{display:table;margin:0;min-width:760px}.portfolio-table th{background:#f6f8fb;color:#68758a;font-size:9px;font-weight:800;border-bottom:1px solid #e4e9f0}.portfolio-table td{font-size:11px;vertical-align:middle}.project-no{font-weight:900;color:#071f4d}.project-name{font-weight:800;color:#172033}.budget{font-weight:800;color:#071f4d;white-space:nowrap}.status-pill{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:8px;font-weight:900;background:#eef3fa;color:#31527c}.status-pill.ACTIVE{background:#e9f7ef;color:#176a43}.status-pill.DRAFT{background:#fff1df;color:#9b5e00}.row-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.row-actions form{margin:0}.row-actions .btn{padding:7px 10px;font-size:9px}.activate-btn{background:#ce122d!important;color:#fff!important}.empty-state{padding:34px;text-align:center;color:#758399}.empty-state b{display:block;color:#071f4d;font-size:14px;margin-bottom:5px}.project-foot{display:grid;grid-template-columns:1fr auto;gap:14px;align-items:center;margin-top:12px;padding:14px 16px;background:#fff;border:1px solid #e3e8ef;border-radius:12px}.project-foot b{display:block;color:#071f4d;font-size:11px}.project-foot small{color:#758399;font-size:9px}.project-foot .btn{background:#ce122d}.pagination-wrap{direction:ltr;margin-top:14px}@media(max-width:760px){.projects-hero{align-items:flex-start;flex-direction:column}.projects-hero h2{font-size:20px}.section-title{align-items:flex-start;flex-direction:column}.project-foot{grid-template-columns:1fr}.project-foot .btn{width:100%;text-align:center}}
</style>
<div class="projects-page">
<section class="projects-hero">
    <div>
        <span class="eyebrow">مشاريعنا · UNIFCO PROJECTS</span>
        <h2>إدارة المشاريع بكفاءة ووضوح</h2>
        <p>متابعة محفظة مشاريع UNIFCO من التخطيط وحتى التفعيل، مع رؤية موحدة لرقم المشروع والميزانية والحالة التشغيلية.</p>
    </div>
    <div class="hero-actions">
        <a class="btn primary" href="{{ route('projects.projects.create') }}">+ مشروع جديد</a>
        <a class="btn" href="{{ route('dashboard') }}">العودة للرئيسية</a>
    </div>
</section>

@if(session('status'))<p class="notice" style="margin-top:14px">{{ session('status') }}</p>@endif

<div class="section-title">
    <div><h3>محفظة المشاريع · Project Portfolio</h3><small>جميع المشاريع المسجلة مرتبة حسب رقم المشروع</small></div>
    <small>{{ $projects->total() }} مشروع</small>
</div>

<div class="card portfolio-card">
    <div class="table-wrap">
        <table class="portfolio-table">
            <thead><tr><th>رقم المشروع</th><th>اسم المشروع</th><th>الميزانية</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
            <tbody>
            @forelse($projects as $p)
                <tr>
                    <td><span class="project-no">{{ $p->project_no }}</span></td>
                    <td><span class="project-name">{{ $p->name }}</span></td>
                    <td><span class="budget">{{ number_format((float)$p->budget,2) }} SAR</span></td>
                    <td><span class="status-pill {{ strtoupper($p->status) }}">{{ $p->status }}</span></td>
                    <td><div class="row-actions">
                        <a class="btn secondary" href="{{ route('projects.projects.edit',$p) }}">تعديل</a>
                        @if($p->status==='DRAFT')<form method="POST" action="{{ route('projects.projects.activate',$p) }}">@csrf<button class="btn activate-btn" type="submit">تفعيل</button></form>@endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state"><b>لا توجد مشاريع حتى الآن</b><span>ابدأ بإضافة أول مشروع إلى المحفظة.</span></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $projects->links() }}</div>

<div class="project-foot">
    <div><b>جاهز لإضافة مشروع جديد؟</b><small>أنشئ سجل المشروع ثم فعّله عند اكتمال بياناته الأساسية.</small></div>
    <a class="btn" href="{{ route('projects.projects.create') }}">إضافة مشروع</a>
</div>
</div>
@endsection
