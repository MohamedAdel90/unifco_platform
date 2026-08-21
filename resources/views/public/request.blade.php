@php
    $locale = request('lang') === 'en' ? 'en' : 'ar';
    $rtl = $locale === 'ar';
    $isEmergency = $type === 'EMERGENCY_MAINTENANCE';
    $copy = [
        'ar' => [
            'title' => $isEmergency ? 'طلب صيانة طارئة' : 'طلب عرض سعر',
            'eyebrow' => 'UNIFCO · ONE FACILITY SHOP',
            'hero_sub' => $isEmergency ? 'استجابة سريعة للأعطال الحرجة مع تحديد الموقع، الموعد، وصور المعدة لتسريع التوجيه للفريق الفني.' : 'أرسل تفاصيل المشروع أو الخدمة المطلوبة وسيتابع فريق UNIFCO إعداد العرض.',
            'badge' => $isEmergency ? 'EMERGENCY MAINTENANCE' : 'REQUEST FOR QUOTATION',
            'back' => 'العودة للرئيسية',
            'lang' => 'English',
            'request_details' => 'بيانات الطلب',
            'service_type' => 'نوع الخدمة',
            'choose_service' => 'اختر الخدمة',
            'site_short' => 'المدينة / الموقع المختصر',
            'site_short_ph' => 'مثال: الرياض - المنطقة الصناعية',
            'subject' => 'عنوان الطلب',
            'subject_ph' => 'مثال: عطل مولد الطوارئ',
            'details' => 'تفاصيل العطل / الطلب',
            'details_ph' => 'اشرح الأعراض، وقت بداية العطل، وأي ملاحظات فنية مهمة',
            'map_title' => 'موقع العطل على الخريطة',
            'map_hint' => 'حدد موقع المعدة مباشرة على الخريطة أو استخدم موقعك الحالي. سيتم حفظ الإحداثيات والعنوان مع الطلب.',
            'use_location' => 'استخدام موقعي الحالي',
            'map_wait' => 'لم يتم تحديد موقع بعد',
            'address' => 'العنوان التفصيلي',
            'address_ph' => 'اسم الموقع، الشارع، المبنى أو أقرب معلم',
            'coords' => 'الإحداثيات',
            'schedule' => 'الموعد المطلوب',
            'schedule_hint' => 'اختر التاريخ أولًا ثم الوقت المطلوب للوصول أو بدء أعمال الصيانة.',
            'date' => 'التاريخ المطلوب',
            'time' => 'الوقت المطلوب',
            'photos' => 'صور الطلب',
            'equipment_photo' => 'صورة المعدة',
            'equipment_hint' => 'صورة واضحة للمعدة أو اللوحة المتأثرة. JPG / PNG / WebP حتى 8MB.',
            'supporting_photos' => 'صور داعمة للطلب',
            'supporting_hint' => 'حتى 6 صور إضافية للعطل أو القراءات أو موقع العمل.',
            'contact' => 'بيانات التواصل',
            'contact_hint' => 'سيستخدم فريق UNIFCO هذه البيانات للتواصل وتأكيد الاستجابة والموعد.',
            'company' => 'اسم الشركة',
            'responsible' => 'المسؤول عن الطلب',
            'responsible_ph' => 'اسم الشخص المسؤول بالموقع',
            'cr' => 'السجل التجاري',
            'email' => 'البريد الإلكتروني',
            'mobile' => 'رقم الجوال',
            'send' => 'إرسال طلب الصيانة',
            'secure' => 'بيانات الطلب تُرسل بشكل آمن إلى فريق التشغيل في UNIFCO',
            'location_set' => 'تم تحديد الموقع',
            'geo_loading' => 'جارٍ تحديد موقعك...',
            'geo_error' => 'تعذر الوصول إلى موقعك. حدد الموقع يدويًا على الخريطة.',
            'geo_unsupported' => 'المتصفح لا يدعم تحديد الموقع',
        ],
        'en' => [
            'title' => $isEmergency ? 'Emergency Maintenance Request' : 'Request a Quotation',
            'eyebrow' => 'UNIFCO · ONE FACILITY SHOP',
            'hero_sub' => $isEmergency ? 'Fast response for critical failures with location, preferred schedule and equipment photos to help us dispatch the right technical team.' : 'Tell us what you need and the UNIFCO team will prepare the right proposal.',
            'badge' => $isEmergency ? 'EMERGENCY MAINTENANCE' : 'REQUEST FOR QUOTATION',
            'back' => 'Back to Home',
            'lang' => 'العربية',
            'request_details' => 'Request Details',
            'service_type' => 'Service Type',
            'choose_service' => 'Choose service',
            'site_short' => 'City / Short Location',
            'site_short_ph' => 'Example: Riyadh - Industrial Area',
            'subject' => 'Request Title',
            'subject_ph' => 'Example: Emergency generator failure',
            'details' => 'Failure / Request Details',
            'details_ph' => 'Describe the symptoms, when the failure started and any important technical notes',
            'map_title' => 'Failure Location on Map',
            'map_hint' => 'Pin the equipment location on the map or use your current location. Coordinates and address will be saved with the request.',
            'use_location' => 'Use My Current Location',
            'map_wait' => 'No location selected yet',
            'address' => 'Detailed Address',
            'address_ph' => 'Site name, street, building or nearest landmark',
            'coords' => 'Coordinates',
            'schedule' => 'Preferred Schedule',
            'schedule_hint' => 'Select the preferred date first, then the requested arrival or maintenance start time.',
            'date' => 'Preferred Date',
            'time' => 'Preferred Time',
            'photos' => 'Request Photos',
            'equipment_photo' => 'Equipment Photo',
            'equipment_hint' => 'Clear photo of the affected equipment or panel. JPG / PNG / WebP up to 8MB.',
            'supporting_photos' => 'Supporting Photos',
            'supporting_hint' => 'Up to 6 additional photos of the failure, readings or work site.',
            'contact' => 'Contact Information',
            'contact_hint' => 'UNIFCO will use these details to confirm the response and requested schedule.',
            'company' => 'Company Name',
            'responsible' => 'Person Responsible for Request',
            'responsible_ph' => 'Responsible person at site',
            'cr' => 'Commercial Registration',
            'email' => 'Email Address',
            'mobile' => 'Mobile Number',
            'send' => 'Submit Maintenance Request',
            'secure' => 'Your request is securely routed to the UNIFCO operations team',
            'location_set' => 'Location selected',
            'geo_loading' => 'Getting your location...',
            'geo_error' => 'Unable to access your location. Please select it manually on the map.',
            'geo_unsupported' => 'Your browser does not support geolocation',
        ],
    ][$locale];
    $serviceLabels = $locale === 'ar'
        ? ['إدارة مرافق متكاملة','مولدات كهربائية','لوحات ATS','محولات كهربائية','UPS والطاقة غير المنقطعة','أنظمة الجهد المتوسط','صيانة وقائية','صيانة تصحيحية','اختبارات وتشغيل','أخرى']
        : ['Integrated Facility Management','Diesel Generators','ATS Panels','Power Transformers','UPS & Critical Power','Medium Voltage Systems','Preventive Maintenance','Corrective Maintenance','Testing & Commissioning','Other'];
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#071f4d">
<title>UNIFCO | {{ $copy['title'] }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
:root{font-family:"Cairo",Tahoma,Arial,sans-serif;--navy:#071f4d;--navy2:#0c315f;--red:#e20b24;--ink:#10233e;--muted:#69788d;--line:#dce4ee;--soft:#f5f8fb;--white:#fff;--shadow:0 22px 60px rgba(7,31,77,.12)}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:#f5f8fb;color:var(--ink);font-family:"Cairo",Tahoma,Arial,sans-serif}.page-top{background:#fff;border-bottom:1px solid #e8edf3}.topbar{width:min(1180px,94%);height:92px;margin:auto;display:flex;align-items:center;gap:18px}.logo{width:170px;height:70px;object-fit:contain;object-position:{{ $rtl ? 'right' : 'left' }} center}.top-actions{margin-{{ $rtl ? 'right' : 'left' }}:auto;display:flex;align-items:center;gap:10px}.link-btn,.lang-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 15px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:800}.link-btn{color:var(--navy)}.lang-btn{border:1px solid #cfd9e5;color:var(--navy);background:#fff}.hero{position:relative;min-height:330px;overflow:hidden;color:#fff;background:linear-gradient({{ $rtl ? '90deg' : '270deg' }},rgba(6,25,53,.98) 0%,rgba(6,32,67,.92) 46%,rgba(6,32,67,.52) 70%,rgba(6,32,67,.20) 100%),url('/images/home/hero-electrical.svg?v=20260821-11') center/cover no-repeat}.hero:after{content:"";position:absolute;inset:auto 0 0;height:4px;background:var(--red)}.hero-inner{position:relative;z-index:2;width:min(1180px,94%);min-height:330px;margin:auto;display:flex;align-items:center}.hero-copy{max-width:720px;padding:44px 0}.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border:1px solid rgba(255,255,255,.3);border-radius:999px;background:rgba(255,255,255,.07);font-size:11px;font-weight:800;letter-spacing:.4px}.hero-badge:before{content:"";width:9px;height:9px;border-radius:50%;background:var(--red);box-shadow:0 0 0 5px rgba(226,11,36,.18)}.hero .eyebrow{margin-top:18px;font-size:13px;font-weight:800;letter-spacing:.5px;color:#d9e7f6}.hero h1{font-size:clamp(36px,5vw,58px);line-height:1.2;margin:6px 0 13px;font-weight:900}.hero p{max-width:650px;margin:0;color:#dce7f4;line-height:1.9;font-size:14px}.shell{width:min(1180px,94%);margin:-44px auto 52px;position:relative;z-index:5}.card{background:#fff;border:1px solid #e3e9f1;border-radius:20px;box-shadow:var(--shadow);overflow:hidden}.card-head{display:flex;align-items:center;gap:14px;padding:23px 28px;background:linear-gradient(180deg,#fff,#fbfcfe);border-bottom:1px solid #e8edf3}.card-head-icon{width:48px;height:48px;border-radius:12px;background:#eef3fa;color:var(--navy);display:grid;place-items:center;font-size:21px;font-weight:900}.card-head h2{margin:0;color:var(--navy);font-size:19px}.card-head p{margin:3px 0 0;color:var(--muted);font-size:11px}.form-body{padding:0 28px 30px}.section{padding:26px 0;border-bottom:1px solid #edf1f5}.section:last-of-type{border-bottom:0}.section-title{display:flex;align-items:center;gap:10px;margin-bottom:4px;color:var(--navy);font-size:16px;font-weight:900}.section-title span{width:30px;height:30px;border-radius:8px;background:#eef3fa;display:grid;place-items:center;font-size:14px}.hint{margin:0 0 16px;color:var(--muted);font-size:11.5px;line-height:1.8}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.field{display:grid;gap:7px;font-size:12.5px;font-weight:800;color:#243b59}.field.full{grid-column:1/-1}.required{color:var(--red)}input,select,textarea{width:100%;min-height:48px;padding:11px 13px;border:1px solid #cfd9e5;border-radius:9px;background:#fff;color:#10233e;font:inherit;outline:0;transition:.16s}textarea{min-height:135px;resize:vertical}input:focus,select:focus,textarea:focus{border-color:#738dad;box-shadow:0 0 0 3px rgba(7,31,77,.06)}.map-panel{border:1px solid #dce4ee;border-radius:14px;padding:15px;background:#fbfcfe}.map-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px}.btn{border:0;border-radius:9px;min-height:45px;padding:11px 18px;background:var(--red);color:#fff;font:inherit;font-size:12px;font-weight:900;cursor:pointer}.btn.navy{background:var(--navy)}.map-status{font-size:11px;color:var(--muted)}.map{height:350px;border:1px solid #dce4ee;border-radius:11px;overflow:hidden;z-index:1}.coords{display:grid;grid-template-columns:1.4fr .6fr;gap:12px;margin-top:12px}.upload{border:1.5px dashed #bac8d8;border-radius:13px;padding:17px;background:#fbfdff}.upload-title{display:flex;align-items:center;gap:9px;margin-bottom:8px;font-weight:900;color:var(--navy);font-size:13px}.upload-title span{width:34px;height:34px;border-radius:9px;background:#eef3fa;display:grid;place-items:center}.upload input{min-height:44px;padding:8px;background:#fff}.preview-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:9px;margin-top:11px}.preview-grid img{width:100%;height:105px;object-fit:cover;border-radius:8px;border:1px solid #dce4ee}.contact-box{background:linear-gradient(135deg,#f7faff,#fff);border:1px solid #dfe7f0;border-radius:14px;padding:18px}.err{margin:20px 28px 0;background:#fff0f2;border:1px solid #fac8d0;color:#9f1f31;border-radius:10px;padding:12px 14px;font-size:12px;font-weight:700}.footer-submit{display:flex;align-items:center;justify-content:space-between;gap:18px;padding-top:8px}.secure-note{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:10.5px}.secure-note:before{content:"✓";width:24px;height:24px;border-radius:50%;background:#eaf7ef;color:#16854b;display:grid;place-items:center;font-weight:900}.submit-btn{min-width:220px;min-height:52px;font-size:13px}.mini-strip{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid #e8edf3;background:#fbfcfe}.mini-item{padding:15px 18px;border-{{ $rtl ? 'left' : 'right' }}:1px solid #e8edf3;text-align:center}.mini-item:last-child{border:0}.mini-item b{display:block;color:var(--navy);font-size:11px}.mini-item span{color:var(--muted);font-size:9px}@media(max-width:780px){.topbar{height:78px}.logo{width:140px;height:60px}.link-btn{display:none}.hero{min-height:290px}.hero-inner{min-height:290px}.hero-copy{padding:34px 0}.shell{margin-top:-30px}.form-body{padding:0 18px 24px}.card-head{padding:19px}.grid,.coords{grid-template-columns:1fr}.map{height:300px}.preview-grid{grid-template-columns:1fr 1fr}.footer-submit{align-items:stretch;flex-direction:column}.submit-btn{width:100%}.mini-strip{grid-template-columns:1fr 1fr}}@media(max-width:480px){.hero h1{font-size:34px}.hero p{font-size:12px}.lang-btn{padding:9px 11px}.card{border-radius:15px}.preview-grid{grid-template-columns:1fr 1fr}.mini-strip{grid-template-columns:1fr}.mini-item{border:0;border-bottom:1px solid #e8edf3}}</style>
</head>
<body>
<header class="page-top"><div class="topbar"><img class="logo" src="{{ route('brand.logo') }}" alt="UNIFCO"><div class="top-actions"><a class="link-btn" href="{{ route('public.home',['lang'=>$locale]) }}">{{ $copy['back'] }}</a><a class="lang-btn" href="{{ $isEmergency ? route('public.emergency',['lang'=>$locale === 'ar' ? 'en' : 'ar']) : route('public.quote',['lang'=>$locale === 'ar' ? 'en' : 'ar']) }}">◎ &nbsp; {{ $copy['lang'] }}</a></div></div></header>
<section class="hero"><div class="hero-inner"><div class="hero-copy"><div class="hero-badge">{{ $copy['badge'] }}</div><div class="eyebrow">{{ $copy['eyebrow'] }}</div><h1>{{ $copy['title'] }}</h1><p>{{ $copy['hero_sub'] }}</p></div></div></section>
<main class="shell"><form class="card" method="POST" enctype="multipart/form-data" action="{{ route('public.request.store') }}">@csrf<input type="hidden" name="request_type" value="{{ $type }}"><div class="card-head"><div class="card-head-icon">⚡</div><div><h2>{{ $copy['request_details'] }}</h2><p>UNIFCO Facility & Electrical Maintenance Service Desk</p></div></div>@if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif<div class="form-body"><section class="section"><div class="section-title"><span>01</span>{{ $copy['request_details'] }}</div><div class="grid"><label class="field">{{ $copy['service_type'] }} <span class="required">*</span><select name="service_category" required><option value="">{{ $copy['choose_service'] }}</option>@foreach($serviceLabels as $service)<option value="{{ $service }}" @selected(old('service_category')===$service)>{{ $service }}</option>@endforeach</select></label><label class="field">{{ $copy['site_short'] }} @if($isEmergency)<span class="required">*</span>@endif<input name="site_city" value="{{ old('site_city') }}" @required($isEmergency) placeholder="{{ $copy['site_short_ph'] }}"></label><label class="field full">{{ $copy['subject'] }} <span class="required">*</span><input name="subject" value="{{ old('subject') }}" required placeholder="{{ $copy['subject_ph'] }}"></label><label class="field full">{{ $copy['details'] }} <span class="required">*</span><textarea name="details" required placeholder="{{ $copy['details_ph'] }}">{{ old('details') }}</textarea></label></div></section>@if($isEmergency)<section class="section"><div class="section-title"><span>02</span>{{ $copy['map_title'] }}</div><p class="hint">{{ $copy['map_hint'] }}</p><div class="map-panel"><div class="map-toolbar"><button class="btn navy" id="use-location" type="button">⌖ &nbsp; {{ $copy['use_location'] }}</button><span class="map-status" id="map-status">{{ $copy['map_wait'] }}</span></div><div id="request-map" class="map"></div><div class="coords"><label class="field">{{ $copy['address'] }} <span class="required">*</span><input id="site-address" name="site_address" value="{{ old('site_address') }}" required placeholder="{{ $copy['address_ph'] }}"></label><label class="field">{{ $copy['coords'] }}<input id="coords-display" value="{{ old('latitude') && old('longitude') ? old('latitude').', '.old('longitude') : '' }}" readonly></label></div><input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}"><input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}"></div></section><section class="section"><div class="section-title"><span>03</span>{{ $copy['schedule'] }}</div><p class="hint">{{ $copy['schedule_hint'] }}</p><div class="grid"><label class="field">{{ $copy['date'] }} <span class="required">*</span><input type="date" name="requested_date" value="{{ old('requested_date') }}" min="{{ now()->toDateString() }}" required></label><label class="field">{{ $copy['time'] }} <span class="required">*</span><input type="time" name="requested_time" value="{{ old('requested_time') }}" required></label></div></section><section class="section"><div class="section-title"><span>04</span>{{ $copy['photos'] }}</div><div class="grid"><div class="upload"><div class="upload-title"><span>▣</span>{{ $copy['equipment_photo'] }} <span class="required">*</span></div><label class="field"><input id="equipment-image" type="file" name="equipment_image" accept="image/jpeg,image/png,image/webp" capture="environment" required></label><p class="hint">{{ $copy['equipment_hint'] }}</p><div id="equipment-preview" class="preview-grid"></div></div><div class="upload"><div class="upload-title"><span>＋</span>{{ $copy['supporting_photos'] }}</div><label class="field"><input id="supporting-images" type="file" name="supporting_images[]" accept="image/jpeg,image/png,image/webp" multiple></label><p class="hint">{{ $copy['supporting_hint'] }}</p><div id="supporting-preview" class="preview-grid"></div></div></div></section>@endif<section class="section"><div class="section-title"><span>{{ $isEmergency ? '05' : '02' }}</span>{{ $copy['contact'] }}</div><p class="hint">{{ $copy['contact_hint'] }}</p><div class="contact-box"><div class="grid"><label class="field">{{ $copy['company'] }} <span class="required">*</span><input name="company_name" value="{{ old('company_name') }}" required></label>@if($isEmergency)<label class="field">{{ $copy['responsible'] }} <span class="required">*</span><input name="responsible_person" value="{{ old('responsible_person') }}" required placeholder="{{ $copy['responsible_ph'] }}"></label>@endif<label class="field">{{ $copy['cr'] }} <span class="required">*</span><input name="commercial_registration" value="{{ old('commercial_registration') }}" required></label><label class="field">{{ $copy['email'] }} <span class="required">*</span><input type="email" name="email" value="{{ old('email') }}" required></label><label class="field">{{ $copy['mobile'] }} <span class="required">*</span><input name="mobile" value="{{ old('mobile') }}" required></label></div></div></section><div class="footer-submit"><div class="secure-note">{{ $copy['secure'] }}</div><button class="btn submit-btn" type="submit">{{ $copy['send'] }}</button></div></div><div class="mini-strip"><div class="mini-item"><b>24/7 Response</b><span>Emergency support</span></div><div class="mini-item"><b>Facility Management</b><span>One facility shop</span></div><div class="mini-item"><b>Electrical Systems</b><span>Generator · ATS · UPS · MV</span></div><div class="mini-item"><b>Digital Tracking</b><span>Request & work order visibility</span></div></div></form></main>
@if($isEmergency)<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>(function(){const latInput=document.getElementById('latitude'),lngInput=document.getElementById('longitude'),addressInput=document.getElementById('site-address'),display=document.getElementById('coords-display'),status=document.getElementById('map-status');const initialLat=parseFloat(latInput.value)||24.7136,initialLng=parseFloat(lngInput.value)||46.6753;const map=L.map('request-map').setView([initialLat,initialLng],latInput.value?16:6);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'}).addTo(map);let marker=null;function setLocation(lat,lng,reverse=true){lat=Number(lat).toFixed(7);lng=Number(lng).toFixed(7);latInput.value=lat;lngInput.value=lng;display.value=lat+', '+lng;if(marker)marker.setLatLng([lat,lng]);else marker=L.marker([lat,lng],{draggable:true}).addTo(map).on('dragend',function(e){const p=e.target.getLatLng();setLocation(p.lat,p.lng,true)});status.textContent=@json($copy['location_set']);if(reverse){fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language={{ $locale }}&lat='+lat+'&lon='+lng).then(r=>r.ok?r.json():null).then(data=>{if(data&&data.display_name&&!addressInput.value)addressInput.value=data.display_name}).catch(()=>{});}}map.on('click',e=>setLocation(e.latlng.lat,e.latlng.lng,true));if(latInput.value&&lngInput.value)setLocation(initialLat,initialLng,false);document.getElementById('use-location').addEventListener('click',function(){if(!navigator.geolocation){status.textContent=@json($copy['geo_unsupported']);return}status.textContent=@json($copy['geo_loading']);navigator.geolocation.getCurrentPosition(pos=>{const {latitude,longitude}=pos.coords;map.setView([latitude,longitude],17);setLocation(latitude,longitude,true)},()=>status.textContent=@json($copy['geo_error']),{enableHighAccuracy:true,timeout:10000})});function previews(inputId,targetId,max){document.getElementById(inputId).addEventListener('change',function(){const target=document.getElementById(targetId);target.innerHTML='';Array.from(this.files).slice(0,max).forEach(file=>{const img=document.createElement('img');img.alt='Preview';img.src=URL.createObjectURL(file);target.appendChild(img)})})}previews('equipment-image','equipment-preview',1);previews('supporting-images','supporting-preview',6)})();</script>@endif
</body></html>