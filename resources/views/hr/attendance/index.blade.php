@extends('layouts.app')
@section('content')
@php($employeeMap=$employees->keyBy('id'))
<style>
.hr2-wrap{display:grid;gap:18px}.hr2-hero{padding:24px;border-radius:20px;background:linear-gradient(135deg,#172a4d,#263f6d);color:#fff;display:flex;justify-content:space-between;gap:20px;align-items:center}.hr2-hero h1{margin:0 0 6px;font-size:26px}.hr2-hero p{margin:0;color:#cdd8e9}.hr2-actions{display:flex;gap:8px;flex-wrap:wrap}.hr2-actions a,.hr2-actions button{border:1px solid #ffffff2d;background:#ffffff12;color:#fff;padding:9px 13px;border-radius:10px;text-decoration:none}.hr2-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.hr2-kpi,.hr2-card{background:#fff;border:1px solid #e4e9f0;border-radius:16px;box-shadow:0 5px 18px #18243b0a}.hr2-kpi{padding:16px}.hr2-kpi small{display:block;color:#75839a;text-transform:uppercase;font-size:10px;letter-spacing:.08em}.hr2-kpi strong{display:block;font-size:26px;color:#1c3157;margin-top:5px}.hr2-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:16px}.hr2-card{padding:18px}.hr2-card h2{font-size:16px;margin:0 0 13px;color:#1d3156}.hr2-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.hr2-form label{font-size:11px;color:#63728b;display:grid;gap:5px}.hr2-form input,.hr2-form select,.hr2-form textarea{border:1px solid #dce3ec;border-radius:9px;padding:9px;background:#fbfcfe}.hr2-form .full{grid-column:1/-1}.hr2-btn{border:0;background:#1e315b;color:#fff;border-radius:9px;padding:10px 14px;font-weight:700;cursor:pointer}.hr2-table{width:100%;border-collapse:collapse}.hr2-table th{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#75839a;text-align:left;padding:10px;border-bottom:1px solid #e8edf3}.hr2-table td{padding:11px 10px;border-bottom:1px solid #eef2f6;font-size:12px}.pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef3f8;font-size:10px;font-weight:700}.late{background:#fff1e8;color:#ad4d12}.ok{background:#eaf7f0;color:#137347}.absent{background:#fdecee;color:#a82737}@media(max-width:900px){.hr2-kpis,.hr2-grid{grid-template-columns:1fr 1fr}.hr2-hero{align-items:flex-start;flex-direction:column}}@media(max-width:620px){.hr2-kpis,.hr2-grid,.hr2-form{grid-template-columns:1fr}}
</style>
<div class="hr2-wrap">
    <section class="hr2-hero">
        <div><small>PEOPLE / ATTENDANCE</small><h1>Attendance & Time</h1><p>Daily time intelligence, lateness, overtime, shift assignment and Ramadan-aware working hours.</p></div>
        <div class="hr2-actions"><a href="{{ route('hr.dashboard') }}">HR Dashboard</a><a href="{{ route('hr.leave.index') }}">Leave Management</a></div>
    </section>
    @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
    <section class="hr2-kpis">
        <div class="hr2-kpi"><small>Present / Recorded</small><strong>{{ $present }}</strong></div>
        <div class="hr2-kpi"><small>Absent</small><strong>{{ $absent }}</strong></div>
        <div class="hr2-kpi"><small>Late Today</small><strong>{{ $late }}</strong></div>
        <div class="hr2-kpi"><small>Overtime Hours</small><strong>{{ number_format($overtime,2) }}</strong></div>
    </section>
    <section class="hr2-grid">
        <div class="hr2-card">
            <h2>Daily Attendance — {{ $date }}</h2>
            <form method="GET" style="margin-bottom:12px"><input type="date" name="date" value="{{ $date }}"><button class="hr2-btn">View day</button></form>
            <div style="overflow:auto"><table class="hr2-table"><thead><tr><th>Employee</th><th>Type</th><th>In / Out</th><th>Worked</th><th>Late</th><th>OT</th><th>Source</th></tr></thead><tbody>
                @forelse($entries as $e)@php($emp=$employeeMap->get($e->employee_id))<tr><td><strong>{{ $emp?->employee_no ?? '#'.$e->employee_id }}</strong><br>{{ $emp?->name }}</td><td><span class="pill {{ $e->attendance_type==='ABSENT'?'absent':'ok' }}">{{ $e->attendance_type }}</span></td><td>{{ $e->check_in_at ?: '—' }} / {{ $e->check_out_at ?: '—' }}</td><td>{{ $e->worked_hours }}h</td><td><span class="pill {{ $e->late_minutes>0?'late':'ok' }}">{{ $e->late_minutes }}m</span></td><td>{{ $e->overtime_hours }}h</td><td>{{ $e->source }}</td></tr>@empty<tr><td colspan="7">No attendance recorded for this day.</td></tr>@endforelse
            </tbody></table></div>
        </div>
        <div class="hr2-card">
            <h2>Record / Recalculate Day</h2>
            <form class="hr2-form" method="POST" action="{{ route('hr.attendance.store') }}">@csrf
                <label class="full">Employee<select name="employee_id" required>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->employee_no }} — {{ $e->name }}</option>@endforeach</select></label>
                <label>Date<input type="date" name="work_date" value="{{ $date }}" required></label>
                <label>Attendance Type<select name="attendance_type"><option>PRESENT</option><option>REMOTE</option><option>FIELD</option><option>DUTY</option><option>ABSENT</option></select></label>
                <label>Check In<input type="time" name="check_in_at"></label><label>Check Out<input type="time" name="check_out_at"></label>
                <label>Source<select name="source"><option>MANUAL</option><option>DEVICE</option><option>MOBILE</option><option>IMPORT</option></select></label>
                <label class="full">Notes<textarea name="notes" rows="2"></textarea></label><div class="full"><button class="hr2-btn">Calculate & Save</button></div>
            </form>
        </div>
    </section>
    <section class="hr2-grid">
        <div class="hr2-card"><h2>Work Schedules & Saudi Working Time</h2><div style="overflow:auto"><table class="hr2-table"><thead><tr><th>Code</th><th>Schedule</th><th>Hours</th><th>Grace</th><th>Ramadan</th></tr></thead><tbody>@forelse($schedules as $s)<tr><td>{{ $s->code }}</td><td><strong>{{ $s->name }}</strong><br>{{ $s->starts_at }}–{{ $s->ends_at }}</td><td>{{ $s->daily_hours }}h</td><td>{{ $s->grace_minutes }}m</td><td>{{ $s->ramadan_mode ? $s->ramadan_daily_hours.'h active' : 'Off' }}</td></tr>@empty<tr><td colspan="5">Create your first work schedule.</td></tr>@endforelse</tbody></table></div></div>
        <div class="hr2-card"><h2>Create Work Schedule</h2><form class="hr2-form" method="POST" action="{{ route('hr.attendance.schedules.store') }}">@csrf
            <label>Code<input name="code" placeholder="RDX-STD" required></label><label>Name<input name="name" placeholder="Riyadh Standard" required></label>
            <label>Starts<input type="time" name="starts_at" value="08:00" required></label><label>Ends<input type="time" name="ends_at" value="17:00" required></label>
            <label>Break Minutes<input type="number" name="break_minutes" value="60" required></label><label>Grace Minutes<input type="number" name="grace_minutes" value="10" required></label>
            <label>Daily Hours<input type="number" step="0.25" name="daily_hours" value="8" required></label><label>Ramadan Hours<input type="number" step="0.25" name="ramadan_daily_hours" value="6" required></label>
            <label class="full"><span><input type="checkbox" name="ramadan_mode" value="1"> Ramadan working-hours mode</span></label><div class="full"><button class="hr2-btn">Create Schedule</button></div>
        </form></div>
    </section>
    @if($schedules->count())<section class="hr2-card"><h2>Assign Employee Schedule</h2><div style="display:flex;gap:10px;flex-wrap:wrap">@foreach($employees->take(12) as $e)<form method="POST" action="{{ route('hr.attendance.employees.schedule',$e) }}" style="display:flex;gap:6px;align-items:center;border:1px solid #e7ecf2;padding:8px;border-radius:10px">@csrf<strong style="font-size:11px">{{ $e->employee_no }}</strong><select name="work_schedule_id">@foreach($schedules as $s)<option value="{{ $s->id }}" @selected($e->getAttribute('work_schedule_id')==$s->id)>{{ $s->code }}</option>@endforeach</select><button class="hr2-btn">Assign</button></form>@endforeach</div></section>@endif
</div>
@endsection
