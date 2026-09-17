{{-- Customer dashboard uses the approved synchronized sidebar and visual styling. --}}
@if($section === 'dashboard')
    @include('customer.dashboard-v3')
    @include('customer.partials.dashboard-sidebar-sync')
@elseif($section === 'work-orders')
    @include('customer.work-orders')
@else
    @include('customer.workspace')
@endif
