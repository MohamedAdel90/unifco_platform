@if($section === 'dashboard')
    @include('customer.dashboard-v3')
@else
    @include('customer.workspace')
@endif
