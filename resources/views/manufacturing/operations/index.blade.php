@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h1 class="mb-3">Manufacturing Operations</h1>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-4">
        <div class="col-lg-4"><div class="card card-body"><h5>Work Center</h5>
            <form method="post" action="{{ route('manufacturing.operations.work-centers.store') }}">@csrf
                <input class="form-control mb-2" name="code" placeholder="Code" required><input class="form-control mb-2" name="name" placeholder="Name" required>
                <input class="form-control mb-2" name="hourly_rate" type="number" step="0.01" min="0" placeholder="Hourly rate" required><button class="btn btn-primary">Create</button>
            </form>
        </div></div>
        <div class="col-lg-4"><div class="card card-body"><h5>BOM</h5>
            <form method="post" action="{{ route('manufacturing.operations.boms.store') }}">@csrf
                <input class="form-control mb-2" name="bom_no" placeholder="BOM No" required><input class="form-control mb-2" name="version" type="number" value="1" min="1" required>
                <select class="form-select mb-2" name="product_item_id" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->item_code }} - {{ $i->name }}</option>@endforeach</select>
                <select class="form-select mb-2" name="lines[0][component_item_id]" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->item_code }} - {{ $i->name }}</option>@endforeach</select>
                <input class="form-control mb-2" name="lines[0][quantity_per]" type="number" step="0.0001" placeholder="Qty per" required>
                <input class="form-control mb-2" name="lines[0][standard_unit_cost]" type="number" step="0.0001" min="0" placeholder="Standard unit cost" required><button class="btn btn-primary">Create BOM</button>
            </form>
        </div></div>
        <div class="col-lg-4"><div class="card card-body"><h5>Routing</h5>
            <form method="post" action="{{ route('manufacturing.operations.routings.store') }}">@csrf
                <input class="form-control mb-2" name="routing_no" placeholder="Routing No" required>
                <select class="form-select mb-2" name="product_item_id" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->item_code }} - {{ $i->name }}</option>@endforeach</select>
                <select class="form-select mb-2" name="operations[0][work_center_id]" required>@foreach($workCenters as $w)<option value="{{ $w->id }}">{{ $w->code }} - {{ $w->name }}</option>@endforeach</select>
                <input class="form-control mb-2" name="operations[0][operation_name]" placeholder="Operation" required><input class="form-control mb-2" name="operations[0][standard_hours]" type="number" step="0.0001" placeholder="Standard hours" required>
                <button class="btn btn-primary">Create Routing</button>
            </form>
        </div></div>
    </div>

    <div class="card mt-4"><div class="card-body"><h5>Production Execution</h5><div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Order</th><th>Status</th><th>Qty</th><th>Costs</th><th>Actions</th></tr></thead><tbody>
        @foreach($orders as $o)<tr><td>{{ $o->order_no }}<br><small>{{ $o->product_name }}</small></td><td>{{ $o->status }}</td><td>{{ $o->produced_quantity }}/{{ $o->planned_quantity }}</td><td>Std {{ $o->standard_cost }} / Actual {{ $o->actual_cost }} / Var {{ $o->cost_variance }}</td><td>
            @if($o->status==='CREATED')<form class="d-flex gap-1 flex-wrap" method="post" action="{{ route('manufacturing.operations.orders.release',$o) }}">@csrf
                <select name="bom_id" class="form-select form-select-sm" required>@foreach($boms as $b)<option value="{{ $b->id }}">{{ $b->bom_no }} v{{ $b->version }}</option>@endforeach</select>
                <select name="routing_id" class="form-select form-select-sm" required>@foreach($routings as $r)<option value="{{ $r->id }}">{{ $r->routing_no }}</option>@endforeach</select>
                <select name="warehouse_code" class="form-select form-select-sm" required>@foreach($warehouses as $w)<option value="{{ $w->code }}">{{ $w->code }}</option>@endforeach</select><button class="btn btn-sm btn-primary">Release</button></form>@endif
            @if($o->status==='RELEASED')
                <form class="d-inline" method="post" action="{{ route('manufacturing.operations.orders.issue',$o) }}">@csrf<button class="btn btn-sm btn-warning">Issue Materials</button></form>
                <form class="d-inline" method="post" action="{{ route('manufacturing.operations.orders.inspect',$o) }}">@csrf<input type="hidden" name="result" value="PASS"><button class="btn btn-sm btn-info">Quality PASS</button></form>
                <form class="d-inline" method="post" action="{{ route('manufacturing.operations.orders.complete',$o) }}">@csrf<input type="hidden" name="produced_quantity" value="{{ $o->planned_quantity }}"><button class="btn btn-sm btn-success">Complete</button></form>
            @endif
        </td></tr>@endforeach
        </tbody></table></div></div></div>
</div>
@endsection
