@php
    $ar = app()->getLocale() === 'ar';
    $activeSites = $sites->filter(fn($site) => strtoupper((string)$site->status) === 'ACTIVE')->count();
    $openRequestsAll = $requests->filter(fn($request) => !in_array(strtoupper((string)$request->status), ['COMPLETED','CLOSED','CANCELLED','REJECTED'], true))->count();
@endphp

@include('customer.partials.portal-shell-open', [
    'activeSection' => 'sites',
    'pageTitle' => $ar ? 'المواقع' : 'Sites',
    'pageDescription' => $ar ? 'مراجعة المواقع المعتمدة وبيانات التواصل والتغطية التشغيلية.' : 'Manage and review authorized customer locations, contacts and operational coverage.',
])

@push('late-styles')
<style>
.portal-content{max-width:none;margin:0;padding-top:24px}.sites-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:18px}.sites-title{display:flex;align-items:center;gap:14px}.sites-title-icon{width:52px;height:52px;border-radius:14px;background:#eaf3ff;color:var(--blue);display:grid;place-items:center}.sites-title-icon .ui-icon{width:27px;height:27px}.sites-head h2{font-size:30px;line-height:1;margin:0 0 7px;letter-spacing:-.03em}.sites-head p{font-size:11px;color:var(--muted);margin:0}.sites-request{min-width:162px;height:44px;background:var(--red);font-size:11px;border-radius:9px}.sites-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}.sites-kpi{min-height:98px;padding:18px;display:grid;grid-template-columns:48px 1fr;gap:14px;align-items:center}.sites-kpi-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#edf5ff;color:var(--blue)}.sites-kpi-icon .ui-icon{width:25px;height:25px}.sites-kpi:nth-child(2) .sites-kpi-icon{background:#eaf8f1;color:var(--green)}.sites-kpi:nth-child(4) .sites-kpi-icon{background:#fff0f2;color:var(--red)}.sites-kpi small{display:block;font-size:10px;color:#53657c;font-weight:700}.sites-kpi b{display:block;font-size:28px;line-height:1;margin:7px 0 4px}.sites-kpi span{font-size:9px;color:var(--muted)}.sites-toolbar{padding:16px 18px;margin-bottom:14px;display:grid;grid-template-columns:minmax(280px,1.8fr) minmax(150px,.6fr) minmax(160px,.7fr) minmax(180px,.7fr) auto;gap:12px;align-items:end}.sites-field{display:grid;gap:7px}.sites-field label{font-size:8px;font-weight:850;color:#53657c}.sites-field input,.sites-field select{height:40px;border:1px solid #d9e3ee;border-radius:8px;background:#fff;padding:0 11px;color:var(--ink);font-size:10px;outline:none}.sites-field input:focus,.sites-field select:focus{border-color:#8eb7e3;box-shadow:0 0 0 3px #1475d11a}.view-toggle{display:flex;justify-content:flex-end;gap:6px}.view-toggle button{height:40px;min-width:68px;border:1px solid #d9e3ee;border-radius:8px;background:#f4f7fb;color:var(--navy);font-size:9px;font-weight:850}.view-toggle button.active{background:var(--navy);color:#fff;border-color:var(--navy)}.sites-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.site-pro{overflow:hidden;padding:0}.site-main{display:grid;grid-template-columns:280px minmax(0,1fr)}.site-visual{position:relative;min-height:245px;background:linear-gradient(145deg,#dbeafe 0%,#9cc6ef 45%,#0a3970 100%);overflow:hidden}.site-visual:before{content:"";position:absolute;inset:0;background:linear-gradient(160deg,transparent 0 47%,rgba(255,255,255,.22) 48% 50%,transparent 51%),linear-gradient(25deg,rgba(5,35,78,.03),rgba(5,35,78,.2))}.site-visual:after{content:"UNIFCO";position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);font-size:30px;letter-spacing:.15em;font-weight:900;color:#fff;text-shadow:0 2px 12px rgba(5,35,78,.28)}.site-status{position:absolute;z-index:2;top:14px;left:14px}.site-info{padding:18px}.site-info-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.site-info h3{font-size:20px;margin:0 0 7px;letter-spacing:-.02em}.site-code{display:inline-flex;padding:5px 8px;border-radius:7px;background:#edf4fd;color:var(--navy);font-size:9px;font-weight:850}.site-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px;margin-top:18px}.site-detail{display:grid;grid-template-columns:25px 1fr;gap:9px;align-items:start}.site-detail .ui-icon{width:19px;height:19px;color:var(--blue);margin-top:1px}.site-detail b{display:block;font-size:10px}.site-detail span{display:block;font-size:8px;color:var(--muted);margin-top:3px}.site-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:18px}.site-stat{padding:10px 11px;border:1px solid #e7edf4;border-radius:10px;background:#f8fbff}.site-stat b{font-size:16px;display:block}.site-stat span{font-size:7.5px;color:var(--muted)}.site-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#e6edf5;border-top:1px solid #e6edf5}.site-actions a{min-height:46px;background:#fff;display:flex;align-items:center;justify-content:center;gap:7px;color:var(--navy);font-size:9px;font-weight:850}.site-actions a.primary{background:linear-gradient(135deg,#0d4e94,#06275c);color:#fff}.sites-empty{padding:34px;text-align:center;color:var(--muted);grid-column:1/-1}.sites-empty strong{display:block;color:var(--ink);font-size:14px;margin-bottom:6px}
@media(max-width:1400px){.sites-grid{grid-template-columns:1fr}.site-main{grid-template-columns:250px minmax(0,1fr)}}
@media(max-width:1050px){.sites-kpis{grid-template-columns:repeat(2,1fr)}.sites-toolbar{grid-template-columns:1fr 1fr}.sites-field.search{grid-column:1/-1}.view-toggle{justify-content:flex-start}.site-main{grid-template-columns:220px 1fr}}
@media(max-width:760px){.sites-head{align-items:flex-start;flex-direction:column}.sites-kpis{grid-template-columns:1fr}.sites-toolbar{grid-template-columns:1fr}.sites-field.search{grid-column:auto}.site-main{grid-template-columns:1fr}.site-visual{min-height:160px}.site-detail-grid,.site-stats{grid-template-columns:1fr 1fr}.site-actions{grid-template-columns:1fr 1fr}.sites-head h2{font-size:25px}}
</style>
@endpush

<div class="sites-head">
    <div class="sites-title"><div class="sites-title-icon">@include('customer.partials.icon',['name'=>'sites'])</div><div><h2>{{ $ar ? 'المواقع' : 'Sites' }}</h2><p>{{ $ar ? 'مراجعة المواقع المعتمدة وجهات الاتصال والتغطية التشغيلية.' : 'Manage and review authorized customer locations, contacts and operational coverage.' }}</p></div></div>
    <a class="portal-btn red sites-request" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">+ {{ $ar ? 'طلب خدمة' : 'Request Service' }}</a>
</div>

<section class="sites-kpis">
    <div class="portal-card sites-kpi"><div class="sites-kpi-icon">@include('customer.partials.icon',['name'=>'sites'])</div><div><small>{{ $ar ? 'إجمالي المواقع' : 'Total Sites' }}</small><b>{{ $sites->count() }}</b><span>{{ $ar ? 'مواقع العميل المعتمدة' : 'Authorized customer locations' }}</span></div></div>
    <div class="portal-card sites-kpi"><div class="sites-kpi-icon">✓</div><div><small>{{ $ar ? 'المواقع النشطة' : 'Active Sites' }}</small><b>{{ $activeSites }}</b><span>{{ $ar ? 'تعمل حاليًا' : 'Currently operational' }}</span></div></div>
    <div class="portal-card sites-kpi"><div class="sites-kpi-icon">@include('customer.partials.icon',['name'=>'assets'])</div><div><small>{{ $ar ? 'الأصول عبر المواقع' : 'Assets Across Sites' }}</small><b>{{ $assets->count() }}</b><span>{{ $ar ? 'إجمالي الأصول والمعدات' : 'Total assets and equipment' }}</span></div></div>
    <div class="portal-card sites-kpi"><div class="sites-kpi-icon">@include('customer.partials.icon',['name'=>'requests'])</div><div><small>{{ $ar ? 'الطلبات المفتوحة' : 'Open Requests' }}</small><b>{{ $openRequestsAll }}</b><span>{{ $ar ? 'طلبات خدمة قيد المتابعة' : 'Service requests in progress' }}</span></div></div>
</section>

<div class="portal-card sites-toolbar">
    <div class="sites-field search"><label>{{ $ar ? 'بحث' : 'Search' }}</label><input id="site-search" placeholder="{{ $ar ? 'اسم الموقع أو الكود أو المدينة أو جهة الاتصال...' : 'Search site name, code, city or contact...' }}"></div>
    <div class="sites-field"><label>{{ $ar ? 'الحالة' : 'Status' }}</label><select id="site-status"><option value="">{{ $ar ? 'كل المواقع' : 'All sites' }}</option><option value="active">ACTIVE</option><option value="inactive">INACTIVE</option></select></div>
    <div class="sites-field"><label>{{ $ar ? 'المدينة / المنطقة' : 'City / Region' }}</label><select id="site-city"><option value="">{{ $ar ? 'كل المدن' : 'All cities' }}</option>@foreach($sites->pluck('city')->filter()->unique()->sort() as $city)<option value="{{ strtolower($city) }}">{{ $city }}</option>@endforeach</select></div>
    <div class="sites-field"><label>{{ $ar ? 'الترتيب' : 'Sort by' }}</label><select id="site-sort"><option value="name">{{ $ar ? 'اسم الموقع' : 'Site name (A - Z)' }}</option><option value="code">{{ $ar ? 'كود الموقع' : 'Site code' }}</option></select></div>
    <div class="view-toggle"><button type="button" class="active">▦ {{ $ar ? 'شبكة' : 'Grid' }}</button><button type="button">☷ {{ $ar ? 'قائمة' : 'List' }}</button></div>
</div>

<section class="sites-grid" id="sites-grid">
@forelse($sites as $site)
    @php
        $siteAssets = $assets->where('customer_site_id',$site->id);
        $siteRequests = $requests->filter(fn($r) => (($r->customer_site_id ?? null) == $site->id || ($r->site_id ?? null) == $site->id) && !in_array(strtoupper((string)$r->status), ['COMPLETED','CLOSED','CANCELLED','REJECTED'], true));
        $siteWorkOrders = $workOrders->filter(fn($wo) => (($wo->asset?->customer_site_id ?? null) == $site->id || ($wo->asset?->site?->id ?? null) == $site->id) && !in_array(strtoupper((string)$wo->status), ['COMPLETED','CLOSED','CANCELLED'], true));
        $siteVisits = $upcomingPlans->filter(fn($plan) => (($plan->asset?->customer_site_id ?? null) == $site->id || ($plan->asset?->site?->id ?? null) == $site->id));
        $searchText = strtolower(trim($site->name.' '.$site->site_code.' '.$site->city.' '.$site->contact_name.' '.$site->contact_mobile));
    @endphp
    <article class="portal-card site-pro" data-name="{{ strtolower($site->name) }}" data-code="{{ strtolower($site->site_code) }}" data-status="{{ strtolower($site->status) }}" data-city="{{ strtolower($site->city) }}" data-search="{{ $searchText }}">
        <div class="site-main">
            <div class="site-visual"><span class="portal-pill {{ strtoupper((string)$site->status)==='ACTIVE' ? 'green' : 'amber' }} site-status">● {{ $site->status }}</span></div>
            <div class="site-info">
                <div class="site-info-top"><div><h3>{{ $site->name }}</h3><span class="site-code">{{ $site->site_code }}</span></div></div>
                <div class="site-detail-grid">
                    <div class="site-detail">@include('customer.partials.icon',['name'=>'sites'])<div><b>{{ $site->city ?: ($ar ? 'المدينة غير محددة' : 'City not specified') }}</b><span>{{ $site->address ?: ($ar ? 'العنوان غير محدد' : 'Address not specified') }}</span></div></div>
                    <div class="site-detail">@include('customer.partials.icon',['name'=>'account'])<div><b>{{ $site->contact_name ?: ($ar ? 'مسؤول الموقع' : 'Site Contact') }}</b><span>{{ $ar ? 'جهة الاتصال الرئيسية' : 'Main contact' }}</span></div></div>
                    <div class="site-detail">@include('customer.partials.icon',['name'=>'phone'])<div><b>{{ $site->contact_mobile ?: '—' }}</b><span>{{ $ar ? 'رقم التواصل' : 'Contact phone' }}</span></div></div>
                    <div class="site-detail">@include('customer.partials.icon',['name'=>'contracts'])<div><b>{{ $ar ? 'مصرح' : 'Authorized' }}</b><span>{{ $ar ? 'الوصول / التصريح' : 'Access / Permit' }}</span></div></div>
                </div>
                <div class="site-stats">
                    <div class="site-stat"><b>{{ $siteAssets->count() }}</b><span>{{ $ar ? 'أصول' : 'Assets' }}</span></div>
                    <div class="site-stat"><b>{{ $siteRequests->count() }}</b><span>{{ $ar ? 'طلبات مفتوحة' : 'Open Requests' }}</span></div>
                    <div class="site-stat"><b>{{ $siteWorkOrders->count() }}</b><span>{{ $ar ? 'أوامر عمل نشطة' : 'Active Work Orders' }}</span></div>
                    <div class="site-stat"><b>{{ $siteVisits->count() }}</b><span>{{ $ar ? 'زيارات قادمة' : 'Upcoming Visits' }}</span></div>
                </div>
            </div>
        </div>
        <div class="site-actions">
            <a href="{{ route('customer.portal',['site_id'=>$site->id]) }}">{{ $ar ? 'عرض الموقع' : 'View Site' }}</a>
            <a href="{{ route('customer.section',['section'=>'assets','site_id'=>$site->id]) }}">{{ $ar ? 'عرض الأصول' : 'View Assets' }}</a>
            <a href="{{ route('customer.section',['section'=>'requests','site_id'=>$site->id]) }}">{{ $ar ? 'طلبات الخدمة' : 'Service Requests' }}</a>
            <a class="primary" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'site_id'=>$site->id]) }}">+ {{ $ar ? 'طلب خدمة' : 'Request Service' }}</a>
        </div>
    </article>
@empty
    <div class="portal-card sites-empty"><strong>{{ $ar ? 'لا توجد مواقع ضمن نطاقك' : 'No sites in your authorized scope' }}</strong>{{ $ar ? 'ستظهر المواقع المصرح بها هنا.' : 'Authorized customer locations will appear here.' }}</div>
@endforelse
</section>

@include('customer.partials.portal-shell-close')
<script>
(()=>{const grid=document.getElementById('sites-grid'),search=document.getElementById('site-search'),status=document.getElementById('site-status'),city=document.getElementById('site-city'),sort=document.getElementById('site-sort');if(!grid)return;const cards=()=>[...grid.querySelectorAll('.site-pro')];const apply=()=>{const q=(search?.value||'').trim().toLowerCase(),s=(status?.value||'').toLowerCase(),c=(city?.value||'').toLowerCase();cards().forEach(card=>{const ok=(!q||card.dataset.search.includes(q))&&(!s||card.dataset.status===s)&&(!c||card.dataset.city===c);card.style.display=ok?'':'none'});const ordered=cards().sort((a,b)=>(a.dataset[sort?.value||'name']||'').localeCompare(b.dataset[sort?.value||'name']||''));ordered.forEach(card=>grid.appendChild(card))};[search,status,city,sort].forEach(el=>el&&el.addEventListener(el===search?'input':'change',apply));})();
</script>
