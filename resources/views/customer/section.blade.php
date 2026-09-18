{{-- Customer dashboard uses its dedicated command-center layout. --}}
@if($section === 'dashboard')
    @include('customer.dashboard-v3')
@elseif($section === 'work-orders')
    @include('customer.work-orders')
@elseif($section === 'visits')
    @include('customer.visits')
@elseif($section === 'maintenance')
    @include('customer.maintenance-plan')
@elseif($section === 'spare-parts')
    @include('customer.spare-parts')
@elseif($section === 'sites')
    @include('customer.sites')
@elseif($section === 'assets')
    @include('customer.assets')
@else
    @include('customer.workspace')
@endif
