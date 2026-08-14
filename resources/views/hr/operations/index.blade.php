@extends('layouts.app')
@section('content')
<h1>HR Operations</h1>
@if(session('status')) <p>{{ session('status') }}</p> @endif
@if($errors->any()) <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif

<h2>Job Positions</h2>
<form method="post" action="{{ route('hr.operations.positions.store') }}">@csrf
<input name="code" placeholder="Code" required><input name="title" placeholder="Title" required><input name="department" placeholder="Department"><button>Create position</button></form>
<table><tr><th>Code</th><th>Title</th><th>Department</th></tr>@foreach($positions as $p)<tr><td>{{ $p->code }}</td><td>{{ $p->title }}</td><td>{{ $p->department }}</td></tr>@endforeach</table>

<h2>Attendance</h2>
<form method="post" action="{{ route('hr.operations.attendance.store') }}">@csrf
<select name="employee_id">@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->employee_no }} — {{ $e->name }}</option>@endforeach</select>
<input type="date" name="work_date" required><input type="number" step="0.25" name="worked_hours" value="8" required><input type="number" step="0.25" name="overtime_hours" value="0" required><button>Record</button></form>

<h2>Leave</h2>
<form method="post" action="{{ route('hr.operations.leave.store') }}">@csrf
<select name="employee_id">@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select><input name="leave_type" value="ANNUAL" required><input type="date" name="starts_on" required><input type="date" name="ends_on" required><input type="number" step="0.5" name="days" required><input name="reason" placeholder="Reason"><button>Submit leave</button></form>
<table><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Status</th><th>Decision</th></tr>@foreach($leaves as $l)<tr><td>{{ $l->employee_id }}</td><td>{{ $l->leave_type }}</td><td>{{ $l->starts_on->toDateString() }} - {{ $l->ends_on->toDateString() }}</td><td>{{ $l->status }}</td><td>@if($l->status==='PENDING')<form method="post" action="{{ route('hr.operations.leave.decide',$l) }}">@csrf<button name="decision" value="APPROVED">Approve</button><button name="decision" value="REJECTED">Reject</button></form>@endif</td></tr>@endforeach</table>

<h2>Payroll Runs</h2>
<p>Create payroll via the form below. Add one initial employee line; additional payroll-line UX can be expanded in later payroll waves.</p>
<form method="post" action="{{ route('hr.operations.payroll.store') }}">@csrf
<input name="payroll_no" placeholder="PAY-2026-08" required><input type="date" name="period_start" required><input type="date" name="period_end" required><input type="date" name="posting_date" required><input name="currency" value="USD" maxlength="3" required>
<select name="lines[0][employee_id]">@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select><input type="number" step="0.01" name="lines[0][basic_pay]" placeholder="Basic" required><input type="number" step="0.01" name="lines[0][allowances]" value="0" required><input type="number" step="0.01" name="lines[0][deductions]" value="0" required><button>Create payroll</button></form>
@foreach($payrolls as $run)<div><strong>{{ $run->payroll_no }}</strong> {{ $run->status }} — {{ $run->lines->sum('net_pay') }} {{ $run->currency }}
@if($run->status==='DRAFT')<form method="post" action="{{ route('hr.operations.payroll.post',$run) }}">@csrf<input name="expense_account_code" placeholder="Payroll expense" required><input name="payable_account_code" placeholder="Net payroll payable" required><input name="deductions_account_code" placeholder="Deductions payable" required><button>Post to Finance</button></form>@endif</div>@endforeach
@endsection
