@extends('layouts.app')

@section('title', app()->getLocale()==='ar' ? 'مركز قيادة التشغيل' : 'Operations Command Center')
@section('page-title', app()->getLocale()==='ar' ? 'مركز قيادة التشغيل' : 'Operations Command Center')

@section('content')
@php($ar = app()->getLocale()==='ar')
<div class="space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $ar ? 'Operations Manager' : 'Operations Manager' }}</div>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $ar ? 'مركز قيادة التشغيل' : 'Operations Command Center' }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $ar ? 'متابعة الطلبات وأوامر العمل وSLA والأصول ضمن نطاق صلاحيتك.' : 'Requests, work orders, SLA and asset health within your authorised scope.' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($capabilities['maintenance'] ?? false)
                    <a href="{{ route('maintenance.work-orders.index') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">{{ $ar ? 'أوامر العمل' : 'Work Orders' }}</a>
                @endif
                @if($capabilities['crm_manage'] ?? false)
                    <a href="{{ route('admin.public-requests.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">{{ $ar ? 'طلبات الخدمة' : 'Service Requests' }}</a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([
            [$ar?'أوامر مفتوحة':'Open WOs', $openWorkOrders->count(), 'maintenance'],
            [$ar?'متأخرة':'Overdue', $overdueWorkOrders->count(), 'maintenance'],
            [$ar?'حرجة':'Critical', $criticalWorkOrders->count(), 'maintenance'],
            [$ar?'طلبات مفتوحة':'Open Requests', $openServiceRequests->count(), 'crm'],
            [$ar?'تجاوز SLA':'SLA Breaches', $slaBreaches->count(), 'crm'],
            [$ar?'أصول حرجة':'Critical Assets', $criticalAssets->count(), 'eam'],
        ] as [$label,$value,$cap])
            @if($capabilities[$cap] ?? false)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold text-slate-500">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($value) }}</div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">{{ $ar ? 'مركز الإجراءات' : 'Action Center' }}</h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $actionItems->count() }}</span>
            </div>
            <div class="space-y-2">
                @forelse($actionItems->take(8) as $item)
                    <a href="{{ $item['url'] }}" class="flex items-center justify-between rounded-xl border border-slate-100 p-3 hover:bg-slate-50">
                        <div>
                            <div class="text-sm font-semibold text-slate-800">{{ $ar ? $item['ar'] : $item['en'] }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $item['severity'] }}</div>
                        </div>
                        <span class="text-lg font-bold text-slate-900">{{ $item['count'] }}</span>
                    </a>
                @empty
                    <div class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">{{ $ar ? 'لا توجد إجراءات عاجلة ضمن نطاقك.' : 'No urgent actions in your scope.' }}</div>
                @endforelse
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-slate-900">{{ $ar ? 'الصحة التشغيلية' : 'Operational Health' }}</h2>
            <div class="mt-5 text-center">
                <div class="text-5xl font-black text-slate-900">{{ $operationalScore }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-500">{{ $operationalBand }}</div>
            </div>
            <div class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between"><span>{{ $ar?'أداء SLA':'SLA performance' }}</span><b>{{ $slaPerformance }}%</b></div>
                <div class="flex justify-between"><span>{{ $ar?'التزام PM':'PM compliance' }}</span><b>{{ $pmCompliance }}%</b></div>
                <div class="flex justify-between"><span>{{ $ar?'صحة الأصول':'Asset health' }}</span><b>{{ $averageAssetHealth }}%</b></div>
            </div>
        </aside>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-bold text-slate-900">{{ $ar ? 'أحدث أوامر العمل' : 'Recent Work Orders' }}</h2>
            @if($capabilities['maintenance'] ?? false)<a href="{{ route('maintenance.work-orders.index') }}" class="text-sm font-semibold text-slate-600">{{ $ar?'عرض الكل':'View all' }}</a>@endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="border-b text-slate-500"><th class="p-3 text-start">#</th><th class="p-3 text-start">{{ $ar?'الحالة':'Status' }}</th><th class="p-3 text-start">{{ $ar?'الأولوية':'Priority' }}</th><th class="p-3 text-start">{{ $ar?'البداية المخططة':'Planned start' }}</th></tr></thead>
                <tbody>
                    @forelse($recentWorkOrders as $wo)
                        <tr class="border-b border-slate-100"><td class="p-3 font-semibold">{{ $wo->work_order_no ?? $wo->id }}</td><td class="p-3">{{ $wo->status }}</td><td class="p-3">{{ $wo->priority }}</td><td class="p-3">{{ optional($wo->planned_start)->format('Y-m-d H:i') ?: '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-slate-500">{{ $ar?'لا توجد أوامر عمل ضمن نطاقك.':'No work orders in your scope.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
