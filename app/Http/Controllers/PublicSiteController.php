<?php

namespace App\Http\Controllers;

use App\Models\PublicServiceRequest;
use App\Services\PublicRequestPipelineService;
use Illuminate\Http\{JsonResponse,RedirectResponse,Request,Response};
use Illuminate\Support\Facades\{DB,Log};
use Illuminate\View\View;
use Throwable;

class PublicSiteController extends Controller
{
    public function home(Request $request): Response
    {
        $requested=$request->query('lang');
        if(in_array($requested,['ar','en'],true)) $request->session()->put('public_locale',$requested);
        $locale=in_array($requested,['ar','en'],true)?$requested:$request->session()->get('public_locale','ar');
        $html=view($locale==='en'?'public.home-en':'public.home',compact('locale'))->render();
        if($locale==='ar') {
            $html=str_replace('تم حذف قطاع الضيافة وتوجيه القطاعات نحو المواقع التي تعتمد على التشغيل والصيانة واستمرارية الطاقة.','تغطي خدماتنا القطاعات التي تعتمد على جاهزية المرافق واستمرارية الطاقة الكهربائية.',$html);
            $html=str_replace('href="#services">تعرّف على UNIFCO ←','href="#" id="about-company-trigger">تعرّف على UNIFCO ←',$html);
            $aboutModal=<<<'HTML'
<style id="unifco-company-profile-style">
.company-profile-overlay{position:fixed;inset:0;z-index:120;background:rgba(5,22,48,.72);backdrop-filter:blur(7px);display:none;align-items:center;justify-content:center;padding:24px}.company-profile-overlay.open{display:flex}.company-profile-modal{width:min(1040px,96vw);max-height:88vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 28px 80px rgba(4,24,55,.28);direction:rtl;text-align:right}.company-profile-head{position:sticky;top:0;z-index:2;background:linear-gradient(135deg,#071f4d,#103d73);color:#fff;padding:24px 30px;display:flex;align-items:center;justify-content:space-between;gap:18px}.company-profile-head small{display:block;color:#c7d4e7;font-size:11px;font-weight:800;letter-spacing:.08em}.company-profile-head h2{margin:4px 0 0;font-size:30px}.company-profile-close{width:40px;height:40px;border:1px solid rgba(255,255,255,.28);background:rgba(255,255,255,.1);color:#fff;border-radius:10px;font-size:23px;cursor:pointer}.company-profile-body{padding:30px}.company-profile-intro{font-size:15px;line-height:2.2;color:#44546b;margin:0 0 22px}.company-profile-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:22px}.company-profile-card{border:1px solid #e0e7ef;border-radius:14px;padding:20px;background:#fbfcfe}.company-profile-card.wide{grid-column:1/-1}.company-profile-card h3{color:#071f4d;font-size:21px;margin:0 0 10px;display:flex;align-items:center;gap:9px}.company-profile-card h3:before{content:"";width:5px;height:24px;border-radius:5px;background:#ce122d}.company-profile-card p{margin:0;color:#526177;font-size:14px;line-height:2.1}.company-profile-footer{margin-top:20px;padding:18px 20px;background:#f2f6fa;border-radius:12px;color:#17345e;font-size:13px;line-height:1.9;border-right:4px solid #ce122d}@media(max-width:820px){.company-profile-overlay{padding:10px}.company-profile-modal{max-height:94vh}.company-profile-head{padding:18px 20px}.company-profile-head h2{font-size:24px}.company-profile-body{padding:20px}.company-profile-grid{grid-template-columns:1fr}.company-profile-card.wide{grid-column:auto}}
</style>
<div class="company-profile-overlay" id="company-profile-overlay" aria-hidden="true"><div class="company-profile-modal" role="dialog" aria-modal="true" aria-labelledby="company-profile-title"><div class="company-profile-head"><div><small>UNIFCO · ONE FACILITY SHOP</small><h2 id="company-profile-title">من نحن</h2></div><button type="button" class="company-profile-close" id="company-profile-close" aria-label="إغلاق">×</button></div><div class="company-profile-body"><p class="company-profile-intro">وحدة المرافق منشأة سعودية 100% مصنفة في مجال التشغيل والصيانة والإنشاءات، ولديها اعتماد وعدة عقود مع عدد من الجهات الحكومية والخاصة كمقاول باطن ومزود خدمة. نعمل لتحقيق أعلى متطلبات الجودة في الخدمات المقدمة لعملائنا من خلال فريق عمل ذو كفاءة وخبرة عالية، ويمتلك القدرات الفنية والأدوات اللازمة، مدعومًا بإدارة محترفة وأنظمة تقنية مساعدة.</p><p class="company-profile-intro">في وحدة المرافق للمقاولات، لم نأتِ لنُكرر ما هو قائم، بل جئنا لنُعيد تعريف مفهوم إدارة وتشغيل المرافق، بمنهجٍ يقوم على الإتقان والابتكار والالتزام المطلق بالجودة. نحن لا نقدم خدمات تقليدية، بل نصنع قيمة مستدامة تُمكّن عملاءنا من التركيز على رؤاهم، بينما نتولى نحن التفاصيل التشغيلية بأعلى درجات الدقة والاحتراف. نؤمن أن المرافق ليست مجرد هياكل أو أنظمة، بل كيانات حيوية تنبض بالحركة والتفاعل، وتستحق إدارة ذكية تحفظ كفاءتها وتُعزز من قدرتها على العطاء. ومن هذا المنطلق، عملنا في وحدة المرافق للمقاولات على بناء منظومة تشغيلية متكاملة، تُدار بعقول هندسية وفرق متخصصة وتقنيات تتطور باستمرار.</p><div class="company-profile-grid"><section class="company-profile-card"><h3>رؤيتنا</h3><p>أن نُصبح الشركة السعودية الرائدة إقليميًا في تقديم حلول متكاملة لإدارة المرافق والتشغيل والصيانة، لا باعتبارنا مجرد مزوّد خدمات، بل شريك استراتيجي يدفع عجلة التحول الوطني نحو مدن ذكية، ومجتمعات مستدامة، وبنى تحتية تعمل بكفاءة عالية وتحاكي المستقبل. نطمح أن نكون الاسم الذي يُقترن بالجودة والابتكار والاستدامة في كل منشأة نخدمها، وأن نُحدِث فرقًا حقيقيًا في حياة الناس من خلال تشغيل ذكي يُلبي احتياجات اليوم ويتطور مع تحديات الغد.</p></section><section class="company-profile-card"><h3>رسالتنا</h3><p>رسالتنا تتمثل في بناء بيئة تشغيلية ذكية، آمنة ومتطورة، تمكّن عملاءنا من التركيز على أهدافهم الأساسية بينما نتولى نحن إدارة التفاصيل التقنية والتشغيلية بكل دقة واحتراف. نُؤمن أن إدارة المرافق ليست مجرد خدمات، بل مسؤولية استثمارية تعزز كفاءة الأصول.</p></section><section class="company-profile-card"><h3>ما يميزنا</h3><p>نحن لا نُجيد العمل فقط، بل نُتقنه. ما يميزنا ليس فقط خبرتنا، بل فلسفتنا في التنفيذ. نُفكر كما يُفكر القادة، ونعمل كما يعمل المحترفون، ونسعى كما يسعى الطموحون. نُقدم حلولاً مصممة حسب احتياجات كل عميل، ونبني استراتيجيات مرنة قادرة على التكيّف والنمو.</p></section><section class="company-profile-card wide"><h3>فريقنا وتقنياتنا</h3><p>فريقنا هو رأس مالنا الحقيقي، ويضم نخبة من المهندسين والفنيين والاستشاريين المدربين وفق أعلى المعايير. التكنولوجيا ليست مجرد أداة لدينا، بل جزء من منظومتنا التشغيلية؛ نستخدمها لتحسين الأداء، وتقليل التكاليف، وزيادة الكفاءة التشغيلية.</p></section></div><div class="company-profile-footer">وحدة المرافق للمقاولات — تشغيل وصيانة وإدارة مرافق بمنهج احترافي يرتكز على الجودة، الكفاءة، الابتكار والاستدامة.</div></div></div></div>
<script id="unifco-company-profile-script">(()=>{const trigger=document.getElementById('about-company-trigger'),overlay=document.getElementById('company-profile-overlay'),close=document.getElementById('company-profile-close');if(!trigger||!overlay)return;const open=()=>{overlay.classList.add('open');overlay.setAttribute('aria-hidden','false');document.body.style.overflow='hidden'};const hide=()=>{overlay.classList.remove('open');overlay.setAttribute('aria-hidden','true');document.body.style.overflow=''};trigger.addEventListener('click',e=>{e.preventDefault();open()});close?.addEventListener('click',hide);overlay.addEventListener('click',e=>{if(e.target===overlay)hide()});document.addEventListener('keydown',e=>{if(e.key==='Escape')hide()})})();</script>
HTML;
            $html=str_replace('</body>',$aboutModal.'</body>',$html);
        }
        $cairo='<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style id="unifco-cairo-font">html,body,button,input,select,textarea{font-family:"Cairo",Tahoma,Arial,sans-serif}</style>';
        return response(str_replace('</head>',$cairo.'</head>',$html))->header('X-UNIFCO-Release','home-company-profile-20260830-01')->header('Cache-Control','no-cache, no-store, must-revalidate');
    }

    public function quote(): Response
    {
        return response(view('public.request',['type'=>'QUOTATION'])->render())
            ->header('Cache-Control','no-cache, no-store, must-revalidate')
            ->header('Pragma','no-cache')
            ->header('Expires','0');
    }

    public function emergency(): Response
    {
        return response(view('public.request',['type'=>'EMERGENCY_MAINTENANCE'])->render())
            ->header('Cache-Control','no-cache, no-store, must-revalidate')
            ->header('Pragma','no-cache')
            ->header('Expires','0');
    }

    public function assetLookup(Request $request): JsonResponse
    {
        $data=$request->validate(['key'=>['required','string','max:200']]); $key=trim($data['key']);
        if(filter_var($key,FILTER_VALIDATE_URL)){ $parts=parse_url($key); parse_str($parts['query']??'',$query); $key=trim((string)($query['asset']??$query['token']??basename($parts['path']??''))); }
        $key=preg_replace('/^UNIFCO-ASSET:/i','',$key)??$key;
        $asset=DB::table('assets as a')->leftJoin('customers as c','c.id','=','a.customer_id')->leftJoin('customer_sites as s','s.id','=','a.customer_site_id')
            ->where(fn($q)=>$q->where('a.qr_token',$key)->orWhere('a.asset_code',$key))->where(fn($q)=>$q->whereNull('a.lifecycle_status')->orWhere('a.lifecycle_status','!=','RETIRED'))
            ->select(['a.id','a.asset_code','a.name','a.asset_category','a.manufacturer','a.model_no','a.lifecycle_status','a.operational_status','a.verification_status','c.name as customer_name','s.name as site_name','s.city as site_city','s.address as site_address','s.latitude','s.longitude','s.contact_name','s.contact_mobile'])->first();
        if(!$asset) return response()->json(['message'=>'Asset not found.'],404);
        $serial=DB::table('asset_specifications')->where('asset_id',$asset->id)->whereIn('spec_key',['serial_number','serial_no','serial'])->orderByRaw("FIELD(spec_key, 'serial_number', 'serial_no', 'serial')")->value('spec_value');
        return response()->json(['asset'=>['id'=>$asset->id,'asset_code'=>$asset->asset_code,'name'=>$asset->name,'type'=>$asset->asset_category?:$asset->name,'brand'=>$asset->manufacturer,'model'=>$asset->model_no,'serial_number'=>$serial,'customer_name'=>$asset->customer_name,'site_name'=>$asset->site_name,'site_city'=>$asset->site_city,'site_address'=>$asset->site_address,'latitude'=>$asset->latitude,'longitude'=>$asset->longitude,'contact_name'=>$asset->contact_name,'contact_mobile'=>$asset->contact_mobile,'lifecycle_status'=>$asset->lifecycle_status,'operational_status'=>$asset->operational_status,'verification_status'=>$asset->verification_status]])->header('Cache-Control','no-store, private');
    }

    public function store(Request $request, PublicRequestPipelineService $pipeline): RedirectResponse
    {
        if(!$request->filled('request_intent')&&$request->filled('request_type')){
            $legacyType=strtoupper((string)$request->input('request_type')); $emergency=$legacyType==='EMERGENCY_MAINTENANCE';
            $request->merge([
                'request_intent'=>$legacyType==='QUOTATION'?'QUOTATION':'SERVICE_REQUEST',
                'request_subtype'=>$legacyType==='QUOTATION'?'SPARE_PARTS_QUOTE':($emergency?'URGENT_MAINTENANCE':'ROUTINE_MAINTENANCE'),
                'site_name'=>$request->input('site_name')?:($request->input('site_city')?:'Customer Site'),
                'responsible_person'=>$request->input('responsible_person')?:($request->input('company_name')?:'Request Contact'),
                'site_area'=>$request->input('site_area')?:($request->input('site_city')?:'General'),
                'asset_type'=>$request->input('asset_type')?:($request->input('service_category')?:'GENERAL'),
                'urgency'=>$emergency?'EMERGENCY':($request->input('urgency')?:'NORMAL'),
                'requested_date'=>$request->input('requested_date')?:today()->toDateString(),
                'requested_time'=>$request->input('requested_time')?:'09:00',
            ]);
        }
        $data=$request->validate([
            'request_intent'=>['required','in:QUOTATION,SERVICE_REQUEST,CONSULTATION'],'request_subtype'=>['required','in:SPARE_PARTS_QUOTE,MAINTENANCE_CONTRACT_QUOTE,ROUTINE_MAINTENANCE,URGENT_MAINTENANCE,TECHNICAL_CONSULTATION'],
            'asset_id'=>['nullable','integer','exists:assets,id'],'site_name'=>['required','string','max:180'],'company_name'=>['required','string','max:180'],'commercial_registration'=>['nullable','string','max:60'],'responsible_person'=>['required','string','max:180'],
            'mobile'=>['required','string','max:32'],'email'=>['required','email','max:180'],'site_area'=>['required','string','max:120'],'site_city'=>['required','string','max:120'],'site_address'=>['nullable','string','max:500'],
            'latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],'asset_type'=>['required','string','max:120'],'equipment_brand'=>['nullable','string','max:120'],'equipment_model'=>['nullable','string','max:120'],
            'service_category'=>['required','string','max:120'],'service_other'=>['nullable','string','max:180'],'urgency'=>['required','in:NORMAL,PRIORITY,URGENT,EMERGENCY'],'requested_date'=>['required','date','after_or_equal:today'],'requested_time'=>['required','date_format:H:i'],
            'details'=>['required','string','max:5000'],'equipment_photos'=>['nullable','array','max:6'],'equipment_photos.*'=>['image','mimes:jpg,jpeg,png,webp','max:8192'],'problem_photos'=>['nullable','array','max:6'],'problem_photos.*'=>['image','mimes:jpg,jpeg,png,webp','max:8192'],'previous_reports'=>['nullable','array','max:5'],'previous_reports.*'=>['file','mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png','max:12288'],
        ]);
        $subtypeIntent=['SPARE_PARTS_QUOTE'=>'QUOTATION','MAINTENANCE_CONTRACT_QUOTE'=>'QUOTATION','ROUTINE_MAINTENANCE'=>'SERVICE_REQUEST','URGENT_MAINTENANCE'=>'SERVICE_REQUEST','TECHNICAL_CONSULTATION'=>'CONSULTATION'];
        abort_unless($subtypeIntent[$data['request_subtype']]===$data['request_intent'],422,'Request type and subtype do not match.');
        if(!empty($data['asset_id'])){
            $asset=DB::table('assets as a')->leftJoin('customers as c','c.id','=','a.customer_id')->leftJoin('customer_sites as s','s.id','=','a.customer_site_id')->where('a.id',$data['asset_id'])->select('a.*','c.name as customer_name','s.name as registry_site_name','s.city as registry_site_city','s.address as registry_site_address','s.latitude as registry_latitude','s.longitude as registry_longitude')->first();
            if($asset){
                $data['asset_type']=$asset->asset_category?:$data['asset_type']; $data['equipment_brand']=$asset->manufacturer?:($data['equipment_brand']??null); $data['equipment_model']=$asset->model_no?:($data['equipment_model']??null);
                $data['company_name']=$asset->customer_name?:$data['company_name']; $data['site_name']=$asset->registry_site_name?:$data['site_name']; $data['site_city']=$asset->registry_site_city?:$data['site_city']; $data['site_address']=$asset->registry_site_address?:($data['site_address']??null); $data['latitude']=$asset->registry_latitude??($data['latitude']??null); $data['longitude']=$asset->registry_longitude??($data['longitude']??null);
            }
        }
        $data['request_type']=match($data['request_subtype']){'SPARE_PARTS_QUOTE','MAINTENANCE_CONTRACT_QUOTE'=>'QUOTATION','URGENT_MAINTENANCE'=>'EMERGENCY_MAINTENANCE','ROUTINE_MAINTENANCE'=>'SERVICE_REQUEST',default=>'CONSULTATION'};
        $data['service_family']=strtoupper($data['asset_type'])==='HVAC'?'MEP':'ELECTRICAL';
        $data['subject']=$request->input('subject')?:trim((($data['service_other']??null)?:$data['service_category']).' - '.$data['site_name']);
        if($data['request_subtype']==='URGENT_MAINTENANCE') $data['urgency']='EMERGENCY';
        unset($data['equipment_photos'],$data['problem_photos'],$data['previous_reports']);
        if($request->hasFile('equipment_photos')) $data['equipment_photo_paths']=collect($request->file('equipment_photos'))->map(fn($f)=>$f->store('public-requests/equipment','public'))->values()->all();
        if($request->hasFile('problem_photos')) $data['problem_photo_paths']=collect($request->file('problem_photos'))->map(fn($f)=>$f->store('public-requests/problems','public'))->values()->all();
        if($request->hasFile('previous_reports')) $data['previous_report_paths']=collect($request->file('previous_reports'))->map(fn($f)=>$f->store('public-requests/reports','public'))->values()->all();
        $record=DB::transaction(function()use($data){
            $counter=DB::table('public_request_counters')->where('key','public_request_ticket')->lockForUpdate()->first();
            if(!$counter){DB::table('public_request_counters')->insert(['key'=>'public_request_ticket','last_number'=>926000000,'created_at'=>now(),'updated_at'=>now()]);$next=926000001;}else{$next=((int)$counter->last_number)+1;}
            DB::table('public_request_counters')->where('key','public_request_ticket')->update(['last_number'=>$next,'updated_at'=>now()]);
            $code=match($data['request_subtype']){'SPARE_PARTS_QUOTE'=>'UNQ-','MAINTENANCE_CONTRACT_QUOTE'=>'UNM-','ROUTINE_MAINTENANCE'=>'UNRM-','URGENT_MAINTENANCE'=>'UNUM-',default=>'UNC-'};
            return PublicServiceRequest::create($data+['ticket_serial'=>$next,'reference_no'=>$code.$next,'submitted_at'=>now(),'status'=>'NEW']);
        });
        try{$pipeline->convert($record);}catch(Throwable $exception){Log::error('Public request pipeline conversion failed after ticket issuance.',['public_service_request_id'=>$record->id,'reference_no'=>$record->reference_no,'request_type'=>$record->request_type,'request_subtype'=>$record->request_subtype,'exception'=>$exception]);}
        return redirect()->route('public.request.received',['reference'=>$record->reference_no,'lang'=>$request->input('lang','ar')]);
    }

    public function received(string $reference): View { $record=PublicServiceRequest::where('reference_no',$reference)->firstOrFail(); return view('public.received',compact('record')); }
    public function adminIndex(): View { return view('public.admin-requests',['requests'=>PublicServiceRequest::latest('submitted_at')->limit(250)->get()]); }
}
