@if($section === 'dashboard')
    @include('customer.dashboard-v3')
@else
    @include('customer.section-legacy')
@endif
