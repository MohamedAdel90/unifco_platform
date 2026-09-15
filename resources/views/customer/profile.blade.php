@php
    $locale = ($locale ?? request('lang')) === 'en' ? 'en' : 'ar';
    $ar = $locale === 'ar';
    $fields = [
        ['contract_manager_name',$ar?'مسؤول العقد':'Contract Manager','text',$customer->contract_manager_name],
        ['contract_manager_title',$ar?'المسمى الوظيفي':'Job Title','text',$customer->contract_manager_title],
        ['email',$ar?'البريد الإلكتروني':'Email Address','email',$customer->email],
        ['phone',$ar?'رقم الجوال':'Mobile Number','text',$customer->phone],
        ['address',$ar?'العنوان':'Address','text',$customer->address],
        ['city',$ar?'المدينة':'City','text',$customer->city],
        ['project_name',$ar?'اسم المشروع':'Project Name','text',$customer->project_name],
    ];
@endphp
@include('customer.partials.portal-shell-open',[
    'customer'=>$customer,
    'activeSection'=>'profile',
    'pageTitle'=>$ar?'ملف الشركة والإعدادات':'Company Profile & Settings',
    'pageDescription'=>$ar?'إدارة بيانات الحساب الموحد وهوية الشركة وأمان الدخول':'Manage the unified account, company identity and sign-in security',
    'locale'=>$locale,
])

<div class="portal-page-head"><div><h2>{{ $ar?'الملف التعريفي للشركة':'Company Profile' }}</h2><p>{{ $ar?'هذه البيانات مشتركة لجميع منسوبي العميل ضمن الدخول الموحد.':'These details are shared across the unified customer login.' }}</p></div><a class="portal-btn soft" href="{{ route('customer.profile.edit',['lang'=>$ar?'en':'ar']) }}">{{ $ar?'English':'العربية' }}</a></div>
@if($errors->any())<div class="profile-error">{{ $errors->first() }}</div>@endif

<div class="profile-grid">
    <section class="portal-card portal-panel profile-wide">
        <h3>{{ $ar?'بيانات الشركة والتواصل':'Company & Contact Details' }}</h3>
        @foreach($fields as [$name,$label,$type,$value])
            <form class="profile-row" method="POST" action="{{ route('customer.profile.update',['lang'=>$locale]) }}">@csrf @method('PUT')
                <label for="profile-{{ $name }}">{{ $label }}</label><input id="profile-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name,$value) }}" placeholder="—"><button class="portal-btn soft">{{ $ar?'حفظ':'Save' }}</button>
            </form>
        @endforeach
    </section>

    <div class="profile-side">
        <section class="portal-card portal-panel"><h3>{{ $ar?'شعار العميل':'Customer Logo' }}</h3><div class="logo-preview">@if($customer->logo_path)<img id="logo-preview" src="{{ asset('storage/'.$customer->logo_path) }}" alt="{{ $customer->name }}">@else<div id="logo-placeholder"><strong>{{ mb_strtoupper(mb_substr($customer->name,0,2)) }}</strong><span>{{ $customer->name }}</span></div><img id="logo-preview" hidden alt="Preview">@endif</div><form method="POST" enctype="multipart/form-data" action="{{ route('customer.profile.logo',['lang'=>$locale]) }}">@csrf<input id="logo-file" class="file-input" type="file" name="logo" accept="image/jpeg,image/png,image/webp" required><button class="portal-btn red full">{{ $ar?'تحديث الشعار':'Update Logo' }}</button></form></section>
        <section class="portal-card portal-panel profile-gap"><h3>{{ $ar?'أمان الدخول الموحد':'Unified Login Security' }}</h3><div class="login-email"><small>{{ $ar?'بريد الدخول':'Login Email' }}</small><strong>{{ auth()->user()->email }}</strong></div><form class="password-form" method="POST" action="{{ route('customer.profile.password',['lang'=>$locale]) }}">@csrf @method('PUT')<input type="password" name="current_password" autocomplete="current-password" placeholder="{{ $ar?'كلمة المرور الحالية':'Current password' }}" required><input type="password" name="password" autocomplete="new-password" placeholder="{{ $ar?'كلمة المرور الجديدة':'New password' }}" required><input type="password" name="password_confirmation" autocomplete="new-password" placeholder="{{ $ar?'تأكيد كلمة المرور':'Confirm password' }}" required><button class="portal-btn full">{{ $ar?'حفظ كلمة المرور':'Update Password' }}</button></form><p class="security-hint">{{ $ar?'استخدم 12 حرفًا على الأقل وتضم حروفًا وأرقامًا.':'Use at least 12 characters including letters and numbers.' }}</p></section>
    </div>
</div>

@push('late-styles')<style>
.profile-error{padding:11px 13px;background:#fff0f2;border:1px solid #facbd2;color:#a11f31;border-radius:9px;margin-bottom:12px;font-size:10px}.profile-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,.75fr);gap:12px}.profile-row{display:grid;grid-template-columns:160px minmax(0,1fr) auto;gap:9px;align-items:center;padding:10px 0;border-bottom:1px solid #edf1f5}.profile-row:last-child{border-bottom:0}.profile-row label{font-size:9px;font-weight:800}.profile-row input,.password-form input,.file-input{width:100%;border:1px solid #d7e0ea;border-radius:8px;padding:9px;background:#fff;font-size:10px}.logo-preview{height:135px;border:1px dashed #c8d3df;border-radius:10px;background:#f8fafc;margin-bottom:10px;display:flex;align-items:center;justify-content:center;text-align:center;padding:12px}.logo-preview img{max-height:105px;max-width:100%;object-fit:contain}.logo-preview strong,.logo-preview span{display:block}.logo-preview strong{font-size:25px}.logo-preview span{font-size:9px;margin-top:4px}.full{width:100%;margin-top:8px}.profile-gap{margin-top:12px}.login-email{border:1px solid #e5ebf2;background:#f8fafc;border-radius:9px;padding:10px;margin-bottom:9px}.login-email small,.login-email strong{display:block}.login-email small,.security-hint{font-size:8px;color:var(--muted)}.login-email strong{font-size:10px;margin-top:4px}.password-form{display:grid;gap:8px}@media(max-width:900px){.profile-grid{grid-template-columns:1fr}}@media(max-width:620px){.profile-row{grid-template-columns:1fr auto}.profile-row label,.profile-row input{grid-column:1}.profile-row button{grid-column:2;grid-row:1/3}}
</style>@endpush
@push('scripts')<script>(()=>{const input=document.getElementById('logo-file');if(!input)return;input.addEventListener('change',()=>{const file=input.files&&input.files[0];if(!file)return;const image=document.getElementById('logo-preview'),placeholder=document.getElementById('logo-placeholder');image.src=URL.createObjectURL(file);image.hidden=false;if(placeholder)placeholder.hidden=true})})();</script>@endpush
@include('customer.partials.portal-shell-close')
