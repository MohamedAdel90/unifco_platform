@extends('layouts.app')
@section('title','فريق المشروع · UNIFCO')
@section('heading','المشاريع / Team & Access')
@section('content')
<style>
.team-page{direction:rtl}.team-hero{background:linear-gradient(135deg,#071f4d,#123d72);border-radius:18px;padding:22px;color:#fff;display:flex;justify-content:space-between;gap:18px;align-items:center}.team-hero h2{margin:5px 0;font-size:22px}.team-hero p{margin:0;color:#d4dfed;font-size:10px}.team-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(320px,.75fr);gap:14px;margin-top:14px}.team-card{padding:16px}.team-card h3{margin:0 0 4px;color:#071f4d;font-size:13px}.team-card p{margin:0 0 14px;color:#748197;font-size:9px}.assign-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.assign-grid label{font-size:9px;font-weight:800;color:#435169}.assign-grid input,.assign-grid select{width:100%;height:38px;border:1px solid #dce3ec;border-radius:9px;padding:0 10px;margin-top:5px;background:#fff}.assign-grid .wide{grid-column:1/-1}.assign-actions{display:flex;justify-content:flex-end;margin-top:12px}.team-table{width:100%;border-collapse:collapse}.team-table th,.team-table td{padding:10px;border-bottom:1px solid #e7ebf1;text-align:right;font-size:9px;vertical-align:middle}.team-table th{color:#6f7c90;background:#f7f9fc}.user-cell b{display:block;color:#12213a;font-size:10px}.user-cell small{display:block;color:#7d899a;margin-top:2px}.tag{display:inline-flex;padding:4px 7px;border-radius:999px;background:#edf3fb;color:#31527c;font-size:8px;font-weight:900}.tag.active{background:#e9f7ef;color:#176a43}.tag.inactive{background:#f5f5f5;color:#777}.scope-note{padding:12px;border-radius:10px;background:#eef6ff;color:#31527c;font-size:9px;line-height:1.7}.empty{padding:30px;text-align:center;color:#7f8b9c}.row-actions{display:flex;gap:6px;align-items:center}.btn.danger{background:#ce122d;color:#fff}.meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.meta{padding:11px;background:#f7f9fc;border-radius:9px}.meta b{display:block;font-size:13px;color:#071f4d}.meta small{font-size:8px;color:#7d899a}.notice{margin-top:12px}
@media(max-width:900px){.team-grid{grid-template-columns:1fr}.team-hero{align-items:flex-start;flex-direction:column}}@media(max-width:600px){.assign-grid{grid-template-columns:1fr}.assign-grid .wide{grid-column:auto}.team-card{padding:12px}.team-table{min-width:760px}.table-wrap{overflow:auto}.team-hero h2{font-size:19px}}
</style>
<div class="team-page">
<section class="team-hero">
    <div><small>PROJECT TEAM & ACCESS</small><h2>{{ $project->project_no }} · {{ $project->name }}</h2><p>إسناد أعضاء الفريق إلى المشروع مع إنشاء Project Scope فعلي يستخدمه نظام الصلاحيات والتوجيه.</p></div>
    <div><a class="btn secondary" href="{{ route('projects.projects.index') }}">العودة للمشاريع</a></div>
</section>

@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error" style="margin-top:12px">{{ implode(' | ',$errors->all()) }}</div>@endif

<div class="team-grid">
    <section class="card team-card">
        <h3>فريق المشروع · Active Assignments</h3>
        <p>كل عضو نشط هنا يحصل على PROJECT Scope لهذا المشروع. الصلاحيات الفعلية تظل محددة بواسطة Master Role.</p>
        <div class="table-wrap">
            <table class="team-table">
                <thead><tr><th>الموظف</th><th>Project Role</th><th>Access</th><th>الفترة</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td class="user-cell"><b>{{ $assignment->user?->name }}</b><small>{{ $assignment->user?->email }}</small></td>
                        <td><span class="tag">{{ $assignment->project_role }}</span></td>
                        <td>{{ str_replace('_',' ',$assignment->access_level) }}</td>
                        <td>{{ $assignment->starts_on?->format('Y-m-d') ?: '—' }} → {{ $assignment->ends_on?->format('Y-m-d') ?: 'Open' }}</td>
                        <td><span class="tag {{ strtolower($assignment->status) }}">{{ $assignment->status }}</span></td>
                        <td>@if($assignment->status==='ACTIVE')<form method="POST" action="{{ route('projects.projects.team.remove',[$project,$assignment]) }}" onsubmit="return confirm('Remove this member from active project access?')">@csrf @method('DELETE')<button class="btn danger" type="submit">إزالة</button></form>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty">لا يوجد فريق مسند لهذا المشروع حتى الآن.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="card team-card">
        <h3>إسناد موظف للمشروع</h3>
        <p>يجب أولاً أن يكون Master Role المختار موجوداً بالفعل على حساب الموظف.</p>
        <form method="POST" action="{{ route('projects.projects.team.assign',$project) }}">@csrf
            <div class="assign-grid">
                <label class="wide">الموظف<select name="user_id" required><option value="">اختر الموظف</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id')==$user->id)>{{ $user->name }} · {{ $user->activeRoles->pluck('code')->join(' + ') ?: $user->role }}</option>@endforeach</select></label>
                <label>Project Role<select name="project_role" required><option value="">اختر الدور</option>@foreach($roles as $role)<option value="{{ $role->code }}" @selected(old('project_role')===$role->code)>{{ $role->name_ar ?: $role->name_en }} · {{ $role->code }}</option>@endforeach</select></label>
                <label>Access<select name="access_level" required><option value="PROJECT" @selected(old('access_level','PROJECT')==='PROJECT')>Full Project Scope</option><option value="ASSIGNED_RECORDS" @selected(old('access_level')==='ASSIGNED_RECORDS')>Assigned Records</option></select></label>
                <label>من<input type="date" name="starts_on" value="{{ old('starts_on') }}"></label>
                <label>إلى<input type="date" name="ends_on" value="{{ old('ends_on') }}"></label>
                <label class="wide">سبب / ملاحظة<input name="reason" value="{{ old('reason') }}" placeholder="مثال: Project Manager assignment"></label>
            </div>
            <div class="assign-actions"><button class="btn" type="submit">حفظ الإسناد</button></div>
        </form>
        <div class="scope-note" style="margin-top:14px"><b>قاعدة النظام:</b><br>Role يحدد ماذا يستطيع الموظف أن يفعل، وProject Scope يحدد أين يستطيع فعله. إزالة الموظف من المشروع تلغي فقط الـScope الذي تم إنشاؤه من Project Team، ولا تحذف Scope أضيف له من مصدر آخر.</div>
        <div class="meta-grid" style="margin-top:12px">
            <div class="meta"><b>{{ $assignments->where('status','ACTIVE')->count() }}</b><small>Active team members</small></div>
            <div class="meta"><b>{{ $assignments->where('status','INACTIVE')->count() }}</b><small>Inactive history</small></div>
        </div>
    </aside>
</div>
</div>
@endsection
