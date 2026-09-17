@if($section === 'dashboard')
    @include('customer.dashboard-v3')
    @include('customer.partials.dashboard-sidebar-sync')
@else
    @include('customer.section-legacy')
@endif
