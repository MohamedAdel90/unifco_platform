{{-- Customer dashboard uses the approved synchronized sidebar and visual styling. --}}
@if($section === 'dashboard')
    @include('customer.dashboard-v3')
    @include('customer.partials.dashboard-sidebar-sync')
@elseif($section === 'work-orders')
    @include('customer.work-orders')
@elseif($section === 'visits')
    @include('customer.visits')
@elseif($section === 'maintenance')
    @include('customer.maintenance-plan')
@elseif($section === 'spare-parts')
    @include('customer.spare-parts')
@else
    @include('customer.workspace')
@endif
