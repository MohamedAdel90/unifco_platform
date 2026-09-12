@extends('layouts.app')
@section('title',($customer->exists?'Edit':'New').' Customer · UNIFCO')
@section('heading',$customer->exists?'Edit Customer':'New Customer')
@section('content')
<style>
.customer-form-page{--cf-navy:#17386f;--cf-navy-dark:#102d5d;--cf-line:#d8e3f0;--cf-soft:#f5f8fc;--cf-muted:#75849a;--cf-red:#e3132c;display:grid;gap:16px}
.customer-form-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.customer-form-toolbar .intro h2{margin:0;color:#102d5d;font-size:22px}.customer-form-toolbar .intro p{margin:5px 0 0;color:var(--cf-muted);font-size:12px}
.customer-progress{display:flex;align-items:center;gap:8px;font-size:11px;color:#66758b;flex-wrap:wrap}.customer-progress .step{display:flex;align-items:center;gap:7px}.customer-progress .dot{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:#edf2f8;color:#75849a;font-weight:800}.customer-progress .step.active .dot{background:var(--cf-navy);color:#fff}.customer-progress .step.active{color:var(--cf-navy);font-weight:800}.customer-progress .line{width:28px;height:1px;background:#d9e2ee}
.customer-card{background:#fff;border:1px solid #e1e8f1;border-radius:14px;box-shadow:0 8px 24px rgba(16,45,93,.045);overflow:hidden}.customer-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid #e6edf5}.customer-card-title{display:flex;align-items:center;gap:10px;color:#102d5d;font-size:16px;font-weight:900}.customer-card-title .section-icon{width:32px;height:32px;border-radius:9px;background:#eef4fb;display:grid;place-items:center;font-size:16px}.customer-card-sub{font-size:10px;color:#6f8096}.customer-card-body{padding:16px 18px}
.customer-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 22px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:11px;font-weight:800;color:#18335f}.req{color:var(--cf-red)}.control-wrap{display:flex;align-items:stretch;border:1px solid #cbd8e7;border-radius:8px;background:#fff;overflow:hidden;transition:.15s ease}.control-wrap:focus-within{border-color:#7f9fca;box-shadow:0 0 0 3px rgba(55,103,165,.08)}.control-icon{width:42px;flex:0 0 42px;display:grid;place-items:center;background:#f6f9fd;border-right:1px solid #dbe5f0;color:#17386f;font-size:16px}.control-wrap input,.control-wrap select,.control-wrap textarea{width:100%;border:0!important;outline:0!important;box-shadow:none!important;background:transparent!important;border-radius:0!important;padding:10px 12px!important;min-height:42px!important;font:inherit;color:#203653}.control-wrap textarea{min-height:82px!important;resize:vertical}.control-wrap input[readonly]{background:#f3f6fa!important;color:#40536e;cursor:not-allowed}.hint{font-size:9px;color:#8793a5}.customer-actions{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.customer-actions .btn{min-height:42px;display:inline-flex;align-items:center;justify-content:center}.customer-actions .primary-action{min-width:230px;background:var(--cf-navy)!important}.customer-actions .primary-action:hover{background:var(--cf-navy-dark)!important}
@media(max-width:760px){.customer-grid{grid-template-columns:1fr;gap:12px}.field.full{grid-column:auto}.customer-card-head{align-items:flex-start;flex-direction:column}.customer-card-sub{display:none}.customer-card-body{padding:14px}.customer-form-toolbar{align-items:flex-start}.customer-progress{width:100%;overflow:auto;flex-wrap:nowrap;padding-bottom:2px}.customer-progress .line{min-width:18px}.customer-actions{flex-direction:column-reverse;align-items:stretch}.customer-actions .btn,.customer-actions .primary-action{width:100%;min-width:0}.control-icon{width:40px;flex-basis:40px}}
</style>

<div class="customer-form-page">
    <div class="customer-form-toolbar">
        <div class="intro">
            <h2>{{ $customer->exists?'Customer Master Data':'Create Customer' }}</h2>
            <p>{{ $customer->exists?'Update the customer master data without changing the customer code.':'Create the customer record first, then continue through the structured onboarding process.' }}</p>
        </div>
        <a class="btn secondary" href="{{ route('crm.customers.index') }}">← Back to customers</a>
    </div>

    @if(!$customer->exists)
    <div class="customer-progress" aria-label="Customer onboarding progress">
        <div class="step active"><span class="dot">1</span><span>Customer Info</span></div><span class="line"></span>
        <div class="step"><span class="dot">2</span><span>Contacts</span></div><span class="line"></span>
        <div class="step"><span class="dot">3</span><span>Review</span></div>
    </div>
    @endif

    @if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif

    <form method="POST" action="{{ $customer->exists?route('crm.customers.update',$customer):route('crm.customers.store') }}">
        @csrf
        @if($customer->exists)@method('PUT')@endif

        <div class="customer-card">
            <div class="customer-card-head">
                <div class="customer-card-title"><span class="section-icon">▦</span>Company Information</div>
                <div class="customer-card-sub">Basic information about the customer company</div>
            </div>
            <div class="customer-card-body">
                <div class="customer-grid">
                    <div class="field"><label>Customer Code</label><div class="control-wrap"><span class="control-icon">#</span><input name="customer_code" value="{{ $customer->customer_code }}" readonly aria-readonly="true"></div><div class="hint">Generated automatically and protected from manual editing.</div></div>
                    <div class="field"><label>Customer Status <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">●</span><select name="status" {{ !$customer->exists?'required':'' }}><option value="ACTIVE" @selected(old('status',$customer->status ?: 'ACTIVE')==='ACTIVE')>Active</option><option value="PROSPECT" @selected(old('status',$customer->status)==='PROSPECT')>Prospect</option><option value="INACTIVE" @selected(old('status',$customer->status)==='INACTIVE')>Inactive</option>@if($customer->exists)<option value="BLOCKED" @selected(old('status',$customer->status)==='BLOCKED')>Blocked</option>@endif</select></div></div>

                    <div class="field"><label>Company Name (English) <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">▤</span><input name="name" value="{{ old('name',$customer->name) }}" placeholder="Enter company name in English" required></div></div>
                    <div class="field"><label>Commercial Registration No. <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">▧</span><input name="commercial_registration" value="{{ old('commercial_registration',$customer->commercial_registration) }}" placeholder="Enter commercial registration number" {{ !$customer->exists?'required':'' }}></div></div>

                    <div class="field"><label>Company Name (Arabic) <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">▤</span><input name="name_ar" value="{{ old('name_ar',$customer->name_ar) }}" placeholder="أدخل اسم الشركة بالعربية" dir="rtl" {{ !$customer->exists?'required':'' }}></div></div>
                    <div class="field"><label>VAT Number</label><div class="control-wrap"><span class="control-icon">%</span><input name="vat_number" value="{{ old('vat_number',$customer->vat_number) }}" placeholder="Enter VAT number (optional)"></div></div>

                    <div class="field"><label>Customer Type <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">◎</span><select name="customer_type" {{ !$customer->exists?'required':'' }}><option value="">Select customer type</option><option value="COMPANY" @selected(old('customer_type',$customer->customer_type)==='COMPANY')>Company</option><option value="GOVERNMENT" @selected(old('customer_type',$customer->customer_type)==='GOVERNMENT')>Government</option><option value="INDIVIDUAL" @selected(old('customer_type',$customer->customer_type)==='INDIVIDUAL')>Individual</option></select></div></div>
                    <div class="field"><label>Main Email <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">✉</span><input type="email" name="email" value="{{ old('email',$customer->email) }}" placeholder="Enter main company email" {{ !$customer->exists?'required':'' }}></div></div>

                    <div class="field"><label>Industry <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">⌂</span><select name="industry" {{ !$customer->exists?'required':'' }}><option value="">Select industry</option>@foreach(['Industrial','Facilities Management','Commercial','Healthcare','Government','Education','Hospitality','Construction','Utilities','Other'] as $industry)<option value="{{ $industry }}" @selected(old('industry',$customer->industry)===$industry)>{{ $industry }}</option>@endforeach</select></div></div>
                    <div class="field"><label>Main Phone <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">☎</span><input name="phone" value="{{ old('phone',$customer->phone) }}" placeholder="Enter main company phone number" inputmode="tel" {{ !$customer->exists?'required':'' }}></div></div>

                    <div class="field full"><label>Website</label><div class="control-wrap"><span class="control-icon">◉</span><input type="url" name="website" value="{{ old('website',$customer->website) }}" placeholder="https://www.example.com (optional)"></div></div>
                </div>
            </div>
        </div>

        <div class="customer-card" style="margin-top:16px">
            <div class="customer-card-head">
                <div class="customer-card-title"><span class="section-icon">♙</span>Primary Contact</div>
                <div class="customer-card-sub">Main contact person for this customer</div>
            </div>
            <div class="customer-card-body">
                <div class="customer-grid">
                    <div class="field"><label>Full Name <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">♙</span><input name="contact_name" value="{{ old('contact_name',$customer->contact_name) }}" placeholder="Enter contact person full name" {{ !$customer->exists?'required':'' }}></div></div>
                    <div class="field"><label>Mobile / Phone <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">☎</span><input name="contact_phone" value="{{ old('contact_phone',$customer->contact_phone) }}" placeholder="Enter mobile number" inputmode="tel" {{ !$customer->exists?'required':'' }}></div></div>
                    <div class="field"><label>Job Title</label><div class="control-wrap"><span class="control-icon">▣</span><input name="contact_title" value="{{ old('contact_title',$customer->contact_title) }}" placeholder="Enter job title (optional)"></div></div>
                    <div class="field"><label>Alternate Phone</label><div class="control-wrap"><span class="control-icon">☎</span><input name="alternate_phone" value="{{ old('alternate_phone',$customer->alternate_phone) }}" placeholder="Enter alternate phone number (optional)" inputmode="tel"></div></div>
                    <div class="field full"><label>Email <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">✉</span><input type="email" name="contact_email" value="{{ old('contact_email',$customer->contact_email) }}" placeholder="Enter contact email" {{ !$customer->exists?'required':'' }}></div></div>
                </div>
            </div>
        </div>

        <div class="customer-card" style="margin-top:16px">
            <div class="customer-card-head">
                <div class="customer-card-title"><span class="section-icon">⌖</span>Location Information</div>
                <div class="customer-card-sub">Main location of the customer</div>
            </div>
            <div class="customer-card-body">
                <div class="customer-grid">
                    <div class="field"><label>Country <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">◎</span><select name="country" {{ !$customer->exists?'required':'' }}><option value="Saudi Arabia" @selected(old('country',$customer->country ?: 'Saudi Arabia')==='Saudi Arabia')>Saudi Arabia</option><option value="United Arab Emirates" @selected(old('country',$customer->country)==='United Arab Emirates')>United Arab Emirates</option><option value="Bahrain" @selected(old('country',$customer->country)==='Bahrain')>Bahrain</option><option value="Kuwait" @selected(old('country',$customer->country)==='Kuwait')>Kuwait</option><option value="Oman" @selected(old('country',$customer->country)==='Oman')>Oman</option><option value="Qatar" @selected(old('country',$customer->country)==='Qatar')>Qatar</option></select></div></div>
                    <div class="field"><label>Address <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">⌖</span><textarea name="address" placeholder="Enter full address" {{ !$customer->exists?'required':'' }}>{{ old('address',$customer->address) }}</textarea></div></div>
                    <div class="field"><label>City <span class="req">*</span></label><div class="control-wrap"><span class="control-icon">▤</span><input name="city" list="saudi-cities" value="{{ old('city',$customer->city) }}" placeholder="Select or enter city" {{ !$customer->exists?'required':'' }}><datalist id="saudi-cities"><option value="Riyadh"><option value="Jeddah"><option value="Makkah"><option value="Madinah"><option value="Dammam"><option value="Khobar"><option value="Dhahran"><option value="Tabuk"><option value="Abha"><option value="Hail"><option value="Al Ahsa"><option value="Jubail"></datalist></div></div>
                </div>
            </div>
        </div>

        <div class="customer-card" style="margin-top:16px">
            <div class="customer-card-head"><div class="customer-card-title"><span class="section-icon">▧</span>Additional Information</div><div class="customer-card-sub">Optional notes and internal information</div></div>
            <div class="customer-card-body"><div class="field"><div class="control-wrap"><span class="control-icon">▧</span><textarea name="notes" placeholder="Add any additional notes about this customer (optional)">{{ old('notes',$customer->notes) }}</textarea></div></div></div>
        </div>

        <div class="customer-actions" style="margin-top:16px">
            <a class="btn secondary" href="{{ route('crm.customers.index') }}">Cancel</a>
            <button class="btn primary-action">{{ $customer->exists?'Save Customer':'Create & Start Onboarding' }} →</button>
        </div>
    </form>
</div>
@endsection
