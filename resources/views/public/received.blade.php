@php
$locale=request('lang')==='en'?'en':'ar';
$rtl=$locale==='ar';
$labels=$rtl?[
    'title'=>'تم استلام طلبك بنجاح',
    'lead'=>'تم تسجيل الطلب وتحويله إلى المسار المناسب داخل UNIFCO. سوف نوافيك بالتحديثات في أقرب وقت.',
    'ticket'=>'رقم التذكرة',
    'copy'=>'نسخ رقم التذكرة',
    'copied'=>'تم النسخ',
    'details'=>'تفاصيل الطلب',
    'type'=>'نوع الطلب',
    'customer'=>'اسم العميل',
    'contract'=>'رقم العقد',
    'asset_no'=>'رقم الأصل',
    'equipment'=>'المعدة',
    'site'=>'الموقع',
    'priority'=>'الأولوية',
    'date'=>'الموعد المفضل',
    'contact'=>'مسؤول التواصل',
    'next'=>'ماذا يحدث الآن؟',
    'nextlead'=>'سنتولى متابعة طلبك من خلال الخطوات التالية.',
    'step1'=>'سيقوم فريق UNIFCO بمراجعة الطلب',
    'step2'=>'سيتم التواصل معك لتأكيد الموعد',
    'step3'=>'ستصلك تحديثات الطلب عبر قنوات التواصل',
    'home'=>'العودة للرئيسية',
    'again'=>'إرسال طلب آخر',
    'not_linked'=>'غير مرتبط',
    'not_available'=>'غير متوفر',
    'tagline'=>'شريكك لبناء أصول مستدامة',
]:[
    'title'=>'Request received successfully',
    'lead'=>'Your request has been registered and routed to the appropriate UNIFCO team. We will keep you updated shortly.',
    'ticket'=>'Ticket Number',
    'copy'=>'Copy ticket number',
    'copied'=>'Copied',
    'details'=>'Request Details',
    'type'=>'Request Type',
    'customer'=>'Customer Name',
    'contract'=>'Contract Number',
    'asset_no'=>'Asset Number',
    'equipment'=>'Equipment',
    'site'=>'Site',
    'priority'=>'Priority',
    'date'=>'Preferred Appointment',
    'contact'=>'Contact Person',
    'next'=>'What happens next?',
    'nextlead'=>'We will continue handling your request through the following steps.',
    'step1'=>'UNIFCO team will review the request',
    'step2'=>'We will contact you to confirm the appointment',
    'step3'=>'You will receive request updates through your contact channels',
    'home'=>'Back to Home',
    'again'=>'Submit Another Request',
    'not_linked'=>'Not linked',
    'not_available'=>'Not available',
    'tagline'=>'Your partner for sustainable assets',
];

$typeNames=[
    'SPARE_PARTS_QUOTE'=>$rtl?'عرض سعر قطع غيار':'Spare Parts Quotation',
    'MAINTENANCE_CONTRACT_QUOTE'=>$rtl?'عرض سعر عقد صيانة':'Maintenance Contract Quotation',
    'ROUTINE_MAINTENANCE'=>$rtl?'صيانة عادية':'Routine Maintenance',
    'URGENT_MAINTENANCE'=>$rtl?'صيانة طارئة':'Emergency Maintenance',
    'TECHNICAL_CONSULTATION'=>$rtl?'استشارة فنية':'Technical Consultation',
    'TECHNICAL_VISIT'=>$rtl?'زيارة فنية':'Technical Visit',
];

$serviceRequest=$record->service_request_id?\App\Models\ServiceRequest::find($record->service_request_id):null;
$serviceContract=($serviceRequest && $serviceRequest->service_contract_id)?\App\Models\ServiceContract::find($serviceRequest->service_contract_id):null;
$asset=$record->asset_id?\Illuminate\Support\Facades\DB::table('assets')->where('id',$record->asset_id)->first():null;

$requestType=$typeNames[$record->request_subtype]??$typeNames[$record->request_type]??str_replace('_',' ',$record->request_subtype?:$record->request_type);
$customerName=$record->company_name?:$labels['not_available'];
$contractNo=$serviceContract?->contract_no?:$labels['not_linked'];
$assetNo=$asset->asset_code??$labels['not_linked'];
$equipment=trim(implode(' · ',array_filter([$record->asset_type,$record->equipment_brand,$record->equipment_model])))?:$labels['not_available'];
$site=trim(implode(' · ',array_filter([$record->site_name,$record->site_city])))?:$labels['not_available'];
$priority=$record->urgency?:$labels['not_available'];
$preferredDate=optional($record->requested_date)->format('d/m/Y');
$preferredTime=$record->requested_time;
$appointment=trim(implode(' - ',array_filter([$preferredDate,$preferredTime])))?:$labels['not_available'];
$contact=trim(implode(' · ',array_filter([$record->responsible_person,$record->mobile])))?:$labels['not_available'];
$arrow=$rtl?'←':'→';
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl?'rtl':'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>UNIFCO | {{ $labels['title'] }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{font-family:"Cairo",Tahoma,Arial,sans-serif;--navy:#071f4d;--navy2:#0b3564;--red:#e20b24;--green:#0e9a5a;--ink:#132137;--muted:#7b8da8;--line:#dbe8f7;--soft:#f7fbff;--softBlue:#edf6ff;--softRed:#fff5f6}*{box-sizing:border-box}html{background:#f4f7fb}body{margin:0;background:linear-gradient(180deg,#fff 0,#f7faff 42%,#f4f7fb 100%);color:var(--ink);min-height:100vh;overflow-x:hidden}.page{width:100%;max-width:820px;margin:0 auto;padding:24px 18px 56px}.top{position:relative;text-align:center;padding:10px 20px 16px;overflow:hidden}.top:before,.top:after{content:"";position:absolute;top:10px;width:150px;height:120px;opacity:.42;background:linear-gradient(135deg,#dfe8f2 25%,transparent 25%) 0 0/34px 34px;pointer-events:none}.top:before{left:-28px;transform:skewY(-9deg)}.top:after{right:-28px;transform:scaleX(-1) skewY(-9deg)}.brand{position:relative;z-index:1}.logo{width:118px;height:80px;object-fit:contain;display:block;margin:0 auto}.tagline{position:absolute;right:22px;top:18px;color:var(--navy);font-size:11px;font-weight:800;line-height:1.45;text-align:right;max-width:150px}.tagline:after{content:"";display:block;width:22px;height:3px;background:var(--red);margin-top:7px;border-radius:4px}.success{width:72px;height:72px;border-radius:50%;display:grid;place-items:center;background:#dff7e9;color:var(--green);font-size:38px;font-weight:900;margin:10px auto 12px;box-shadow:0 0 0 9px rgba(38,183,112,.09)}h1{margin:0;color:var(--navy);font-size:34px;line-height:1.35;font-weight:900}.lead{margin:8px auto 0;color:#6d7f99;font-size:14px;line-height:1.9;max-width:620px}.ticket{position:relative;text-align:center;margin:12px 0 18px;border:1px solid #ffd9de;background:linear-gradient(135deg,#fff 0,#fff8f9 50%,#fff 100%);border-radius:18px;padding:18px 22px;box-shadow:0 9px 26px rgba(226,11,36,.045)}.ticket-head{font-size:13px;color:#647996;font-weight:800;text-align:start}.ref{direction:ltr;text-align:center;font-size:34px;font-weight:900;color:var(--red);letter-spacing:.01em;margin:3px 0 9px;overflow-wrap:anywhere}.copy{border:0;background:#ffecef;color:var(--red);border-radius:999px;padding:8px 15px;font:inherit;font-size:11px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:7px}.copy svg{width:16px;height:16px}.card{background:rgba(255,255,255,.95);border:1px solid #d7e8fb;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(20,61,107,.05);margin-top:16px}.card-title{display:flex;align-items:center;gap:10px;color:var(--navy);font-size:20px;font-weight:900;margin-bottom:14px}.title-icon{width:38px;height:38px;border-radius:10px;background:var(--navy);color:#fff;display:grid;place-items:center;flex:0 0 auto}.title-icon svg{width:20px;height:20px}.details{display:grid;grid-template-columns:1fr 1fr;gap:9px}.item{min-height:72px;border:1px solid #deebf8;border-radius:12px;background:linear-gradient(180deg,#fbfdff,#f7fbff);padding:10px 12px;display:grid;grid-template-columns:1fr 38px;gap:9px;align-items:center}.item.full{grid-column:1/-1}.meta{min-width:0}.meta small{display:block;color:#8595ac;font-size:10px;margin-bottom:2px}.meta b{display:block;color:#112c55;font-size:12px;line-height:1.55;overflow-wrap:anywhere}.item-icon{width:36px;height:36px;border-radius:10px;background:#eaf4ff;color:#0d3972;display:grid;place-items:center}.item-icon svg{width:18px;height:18px}.next{padding:18px 18px 16px;background:linear-gradient(180deg,#fafdff,#f2f8ff)}.next-head{display:flex;align-items:center;gap:10px;margin-bottom:4px}.next h2{color:var(--navy);font-size:21px;margin:0}.next p{color:#7486a0;font-size:11px;margin:0 0 16px}.steps{display:grid;grid-template-columns:1fr 40px 1fr 40px 1fr;align-items:start}.step{text-align:center;color:#153460;font-size:11px;font-weight:700;line-height:1.65}.step-icon{width:58px;height:58px;border-radius:50%;background:#edf6ff;border:1px solid #d8eafe;margin:0 auto 8px;display:grid;place-items:center;color:#0b3269;position:relative}.step-icon svg{width:23px;height:23px}.num{position:absolute;top:-7px;inset-inline-start:-2px;background:var(--navy);color:#fff;border-radius:50%;width:25px;height:25px;display:grid;place-items:center;font-size:10px;font-weight:900}.flow{display:flex;align-items:center;justify-content:center;height:58px;color:#345985;font-size:28px;font-weight:400}.actions{display:grid;grid-template-columns:1fr;gap:10px;margin-top:16px}.btn{height:52px;border-radius:12px;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:9px;font-weight:900;font-size:14px}.btn.primary{background:linear-gradient(90deg,var(--navy),#0b3d78);color:#fff;box-shadow:0 8px 18px rgba(7,31,77,.14)}.btn.secondary{background:#fff;color:var(--red);border:2px solid var(--red)}.btn svg{width:18px;height:18px}
@media(max-width:640px){body{background:linear-gradient(180deg,#fff 0,#f8fbff 44%,#f3f7fb 100%)}.page{width:100%;max-width:none;padding:10px 12px 96px}.top{padding:8px 4px 14px}.top:before,.top:after{width:105px;height:82px;opacity:.24}.tagline{display:none}.logo{width:96px;height:66px}.success{width:56px;height:56px;font-size:30px;margin:8px auto 10px;box-shadow:0 0 0 7px rgba(38,183,112,.08)}h1{font-size:26px}.lead{font-size:11.5px;max-width:360px;line-height:1.75}.ticket{margin:8px 0 12px;padding:14px 16px;border-radius:16px}.ticket-head{font-size:11px}.ref{font-size:26px;margin:4px 0 8px}.copy{padding:7px 13px;font-size:10px}.card{padding:13px;margin-top:12px;border-radius:16px}.card-title{font-size:18px;margin-bottom:11px}.title-icon{width:34px;height:34px}.details{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.item{min-height:68px;padding:8px 9px;grid-template-columns:1fr 32px;gap:6px}.item.full{grid-column:1/-1}.meta small{font-size:8.5px}.meta b{font-size:10.5px;line-height:1.45}.item-icon{width:31px;height:31px;border-radius:9px}.item-icon svg{width:16px;height:16px}.next{padding:14px 12px 13px}.next h2{font-size:18px}.next p{font-size:9.5px;margin-bottom:13px}.steps{grid-template-columns:1fr 18px 1fr 18px 1fr}.flow{height:48px;font-size:20px}.step{font-size:8.5px;line-height:1.55}.step-icon{width:46px;height:46px;margin-bottom:6px}.step-icon svg{width:20px;height:20px}.num{width:21px;height:21px;font-size:9px;top:-5px}.actions{margin-top:12px;gap:8px}.btn{height:48px;font-size:13px;border-radius:11px}}
@media(max-width:360px){.page{padding-inline:10px}.details{grid-template-columns:1fr}.item.full{grid-column:auto}.ref{font-size:23px}.steps{grid-template-columns:1fr 14px 1fr 14px 1fr}.step{font-size:7.8px}.step-icon{width:42px;height:42px}.flow{font-size:18px}.card-title{font-size:17px}}
</style>
</head>
<body>
<main class="page">
    <section class="top">
        <div class="tagline">{{ $labels['tagline'] }}</div>
        <div class="brand"><img class="logo" src="{{ route('brand.logo') }}" alt="UNIFCO"></div>
        <div class="success" aria-hidden="true">✓</div>
        <h1>{{ $labels['title'] }}</h1>
        <p class="lead">{{ $labels['lead'] }}</p>
    </section>

    <section class="ticket" aria-label="{{ $labels['ticket'] }}">
        <div class="ticket-head">{{ $labels['ticket'] }}</div>
        <div class="ref" id="ticket-reference">{{ $record->reference_no }}</div>
        <button class="copy" id="copy-ticket" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <span>{{ $labels['copy'] }}</span>
        </button>
    </section>

    <section class="card">
        <div class="card-title">
            <span class="title-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8"/></svg></span>
            {{ $labels['details'] }}
        </div>
        <div class="details">
            <div class="item"><div class="meta"><small>{{ $labels['type'] }}</small><b>{{ $requestType }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-3 3-3-3 3-3z"/></svg></span></div>
            <div class="item"><div class="meta"><small>{{ $labels['customer'] }}</small><b>{{ $customerName }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg></span></div>
            <div class="item"><div class="meta"><small>{{ $labels['asset_no'] }}</small><b dir="ltr">{{ $assetNo }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 7l9 5 9-5-9-5z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/></svg></span></div>
            <div class="item"><div class="meta"><small>{{ $labels['contract'] }}</small><b dir="ltr">{{ $contractNo }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span></div>
            <div class="item"><div class="meta"><small>{{ $labels['equipment'] }}</small><b>{{ $equipment }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1v.1H9.6V21a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4h-.1V9.6H3a1.7 1.7 0 0 0 1.6-1.1 1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1v-.1h4V3a1.7 1.7 0 0 0 1.1 1.6 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.24.36.45.73.6 1 .15.27.28.65.4 1v2c-.12.35-.25.72-.4 1-.15.27-.36.64-.6 1z"/></svg></span></div>
            <div class="item"><div class="meta"><small>{{ $labels['site'] }}</small><b>{{ $site }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span></div>
            <div class="item"><div class="meta"><small>{{ $labels['priority'] }}</small><b>{{ $priority }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20V10M10 20V4M16 20v-7M22 20V7"/></svg></span></div>
            <div class="item"><div class="meta"><small>{{ $labels['date'] }}</small><b dir="ltr">{{ $appointment }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span></div>
            <div class="item full"><div class="meta"><small>{{ $labels['contact'] }}</small><b>{{ $contact }}</b></div><span class="item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3 5.18 2 2 0 0 1 5 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8.9 10.73a16 16 0 0 0 4.37 4.37l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/></svg></span></div>
        </div>
    </section>

    <section class="card next">
        <div class="next-head"><span class="title-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg></span><h2>{{ $labels['next'] }}</h2></div>
        <p>{{ $labels['nextlead'] }}</p>
        <div class="steps">
            <div class="step"><div class="step-icon"><span class="num">1</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8"/></svg></div>{{ $labels['step1'] }}</div>
            <div class="flow">{{ $arrow }}</div>
            <div class="step"><div class="step-icon"><span class="num">2</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3 5.18 2 2 0 0 1 5 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8.9 10.73a16 16 0 0 0 4.37 4.37l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/></svg></div>{{ $labels['step2'] }}</div>
            <div class="flow">{{ $arrow }}</div>
            <div class="step"><div class="step-icon"><span class="num">3</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>{{ $labels['step3'] }}</div>
        </div>
    </section>

    <div class="actions">
        <a class="btn primary" href="{{ route('public.home',['lang'=>$locale]) }}"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 11 12 3l9 8v10h-6v-6H9v6H3V11z"/></svg>{{ $labels['home'] }}</a>
        <a class="btn secondary" href="{{ route('public.request-service',['lang'=>$locale]) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>{{ $labels['again'] }}</a>
    </div>
</main>
<script>
(()=>{const btn=document.getElementById('copy-ticket'),ref=document.getElementById('ticket-reference');if(!btn||!ref)return;const original=btn.querySelector('span')?.textContent||'';btn.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(ref.textContent.trim());const span=btn.querySelector('span');if(span){span.textContent=@json($labels['copied']);setTimeout(()=>span.textContent=original,1600)}}catch(e){const range=document.createRange();range.selectNodeContents(ref);const sel=window.getSelection();sel.removeAllRanges();sel.addRange(range);document.execCommand('copy');sel.removeAllRanges()}})})();
</script>
</body>
</html>