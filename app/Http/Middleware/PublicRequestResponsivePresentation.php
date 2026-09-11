<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestResponsivePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, '<html')) {
            return $response;
        }

        $styles = <<<'HTML'
<style id="unifco-public-request-responsive-final">
html,body{width:100%!important;max-width:100%!important;min-width:0!important;overflow-x:hidden!important}
body{position:relative!important}
*,*::before,*::after{max-width:100%}
img,video,canvas,svg{height:auto;max-width:100%}
.top,.page-head,main,form,#routine-form,.panel,section{min-width:0!important}
.wrap,.top .wrap,main.wrap{width:min(1180px,calc(100% - 32px))!important;max-width:1180px!important;min-width:0!important;margin-inline:auto!important}
.panel{width:100%!important;max-width:100%!important;min-width:0!important}
.service-line,.grid,.visit,.uploads,.asset-methods,.asset-card.show,.customer-details,.uf-customer-card>.grid,.uf-current-customer-details,.uf-spare-details-grid{min-width:0!important}
.service-line>*,.grid>*,.visit>*,.uploads>*,.asset-methods>*,.asset-card.show>*,.customer-details>*,.uf-customer-card>.grid>*,.uf-current-customer-details>*,.uf-spare-details-grid>*{min-width:0!important}
input,select,textarea,button{max-width:100%!important;min-width:0}
.uf-spare-table-wrap{width:100%!important;max-width:100%!important;overflow-x:auto!important;-webkit-overflow-scrolling:touch!important}
.uf-spare-table{width:100%!important;max-width:none!important}

@media(max-width:900px){
 .wrap,.top .wrap,main.wrap{width:calc(100% - 24px)!important}
 .top{height:auto!important;min-height:64px!important;padding:8px 0!important}
 .logo{max-height:44px!important;width:auto!important}
 .page-head{padding:20px 12px 12px!important}
 .page-head h1{font-size:24px!important;line-height:1.35!important}
 .panel{padding:14px!important;border-radius:12px!important}
 .service-line,.visit,.uploads{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .asset-methods{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .asset-card.show,.customer-details,.uf-customer-card>.grid,.uf-current-customer-details,.uf-spare-details-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .span4,.uf-current-customer-details .field.address,.uf-spare-notes{grid-column:1/-1!important}
}

@media(max-width:640px){
 .wrap,.top .wrap,main.wrap{width:calc(100% - 16px)!important}
 .top .wrap{gap:10px!important}
 .back{font-size:10px!important;white-space:nowrap!important}
 .logo{max-width:128px!important;max-height:40px!important}
 .page-head{padding:16px 8px 10px!important}
 .page-head h1{font-size:21px!important}
 .page-head p{font-size:10px!important;line-height:1.7!important}
 .panel{padding:11px!important;margin-bottom:10px!important;border-radius:10px!important}
 .section-title{font-size:13px!important;line-height:1.5!important;flex-wrap:wrap!important}
 .service-line,.grid,.visit,.uploads,.asset-card.show,.customer-details,.uf-customer-card>.grid,.uf-current-customer-details,.uf-spare-details-grid{grid-template-columns:1fr!important}
 .span2,.span4,.uf-current-customer-details .field.address,.uf-spare-notes{grid-column:auto!important}
 .lookup-row,.asset-search.show{display:grid!important;grid-template-columns:1fr!important;width:100%!important}
 #routine-form .lookup-row,.uf-customer-card .lookup-row{display:grid!important;grid-template-columns:1fr!important;width:100%!important}
 #routine-form #customer_number,.uf-customer-card #customer_number,#routine-form #customer-lookup,.uf-customer-card #customer-lookup{width:100%!important;max-width:100%!important;flex:none!important}
 .asset-methods{grid-template-columns:1fr 1fr!important}
 .customer-summary-head{display:grid!important;grid-template-columns:1fr!important;gap:10px!important}
 .customer-name,.detail-value{max-width:100%!important;white-space:normal!important;overflow-wrap:anywhere!important}
 .uf-spare-choice{padding:10px!important;gap:8px!important;align-items:flex-start!important}
 .uf-spare-choice-main{min-width:0!important}
 .uf-spare-choice strong{font-size:12px!important}
 .uf-spare-section{padding:8px!important}
 .uf-spare-section-title{align-items:flex-start!important;flex-direction:column!important;gap:3px!important}
 .uf-spare-assets{display:grid!important;grid-template-columns:1fr!important}
 .uf-spare-asset-chip{max-width:none!important;width:100%!important}
 .uf-spare-table{min-width:760px!important}
 .uf-spare-table th,.uf-spare-table td{white-space:nowrap!important}
 .uf-spare-selected{max-height:300px!important;overflow:auto!important;-webkit-overflow-scrolling:touch!important}
 input,select,textarea,.btn,.uf-spare-option{font-size:16px!important}
 textarea{min-height:90px!important}
}

@media(max-width:390px){
 .wrap,.top .wrap,main.wrap{width:calc(100% - 12px)!important}
 .panel{padding:9px!important}
 .asset-methods{grid-template-columns:1fr!important}
 .num{width:22px!important;height:22px!important;min-width:22px!important}
}
</style>
HTML;

        // Guarantee a correct mobile viewport even if an older presentation layer rewrites the head.
        if (preg_match('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', $html)) {
            $html = preg_replace('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">', $html, 1);
        } else {
            $html = str_replace('<head>', '<head><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">', $html);
        }

        $html = str_replace('</head>', $styles.'</head>', $html);

        if ($request->query('lang') === 'en') {
            $html = preg_replace('/<html\s+lang="ar"\s+dir="rtl">/i', '<html lang="en" dir="ltr">', $html, 1) ?? $html;
            $localization = <<<'HTML'
<style id="unifco-public-request-english-layout">
html[dir="ltr"] body{direction:ltr!important;text-align:left!important}
html[dir="ltr"] .site-header .nav-links,html[dir="ltr"] .site-header .mobile-menu{direction:ltr!important}
html[dir="ltr"] .section-title,html[dir="ltr"] label,html[dir="ltr"] input,html[dir="ltr"] select,html[dir="ltr"] textarea{text-align:left!important}
html[dir="ltr"] select{background-position:right 12px center!important}
</style>
<script id="unifco-public-request-english-localization">
(()=>{
const dictionary={
'طلب خدمة | UNIFCO':'Request Service | UNIFCO','طلب خدمة':'Request Service','تسجيل الدخول':'Login','الرئيسية':'Home','تعرف علينا':'About Us','الخدمات':'Services','القطاعات':'Industries','المشاريع':'Projects','العملاء':'Clients','الوظائف':'Careers','تواصل معنا':'Contact Us','التنقل الرئيسي':'Primary navigation',
'نموذج UNIFCO الموحد للعملاء الحاليين والجدد وجميع أنواع طلبات الخدمة.':'UNIFCO unified form for existing and new customers and all service request types.','نوع العميل':'Customer Type','✓ عميل حالي':'✓ Existing Customer','＋ عميل جديد':'+ New Customer','نوع الخدمة المطلوبة':'Required Service','صيانة':'Maintenance','عرض سعر':'Quotation','استشارة فنية':'Technical Consultation','نوع الطلب':'Request Type','صيانة عادية':'Routine Maintenance','صيانة طارئة':'Emergency Maintenance','عادية':'Routine','مرتفعة':'High','الأولوية':'Priority',
'اختر نوع العميل أولًا، ثم الخدمة ونوع الطلب. يتم عرض النموذج المناسب أسفل هذا السطر.':'Choose the customer type, service, and request type. The appropriate form will appear below.','هذا النوع تم اعتماده للتطوير اللاحق. لم يتم تغيير أي نموذج آخر بالموقع.':'This request type is scheduled for a later development phase.','بيانات العميل':'Customer Information','بيانات العميل الحالي':'Existing Customer Information','رقم العميل':'Customer Number','مثال: CUS-00125':'Example: CUS-00125','جلب البيانات':'Retrieve Details','يتم جلب بيانات العميل تلقائيًا بعد إدخال رقم العميل.':'Customer details are retrieved automatically after entering the customer number.','بانتظار إدخال رقم العميل':'Waiting for customer number','أدخل رقم العميل ثم اضغط «جلب البيانات» لعرض معلوماته هنا.':'Enter the customer number, then select Retrieve Details to view the customer information.','تم التحقق من العميل':'Customer verified','تم جلب بيانات العميل بنجاح.':'Customer details retrieved successfully.','مسؤول التواصل':'Contact Person','رقم الجوال':'Mobile Number','البريد الإلكتروني':'Email Address','المدينة':'City','العنوان المختصر':'Short Address','↻ تغيير العميل':'↻ Change Customer',
'بيانات العقد':'Contract Information','رقم العقد':'Contract Number','اختر عقد العميل':'Select Customer Contract','نوع / عنوان العقد':'Contract Type / Title','حالة العقد':'Contract Status','الموقع والتواصل':'Site & Contact','الموقع المسجل':'Registered Site','اختر الموقع':'Select Site','اسم الموقع':'Site Name','العنوان':'Address','مسؤول الموقع':'Site Contact','رقم التواصل':'Contact Number','خريطة الموقع':'Site Map',
'المعدة وبياناتها':'Equipment Information','اختيار من معدات العقد':'Select Contract Equipment','مسح / إدخال QR':'Scan / Enter QR','رقم الأصل':'Asset Number','السيريال نمبر':'Serial Number','معدة غير تعاقدية':'Non-contract Equipment','المعدات المسجلة على العقد':'Equipment Registered Under Contract','اختر المعدة':'Select Equipment','أدخل القيمة أو امسح QR':'Enter a value or scan QR','بحث عن المعدة':'Find Equipment','اسم المعدة':'Equipment Name','نوع المعدة':'Equipment Type','الشركة المصنعة':'Manufacturer','الموديل':'Model','الحالة التشغيلية':'Operating Status','النوع':'Type',
'وصف سريع للعطل':'Brief Issue Description','اكتب وصفًا مختصرًا وواضحًا للعطل أو الخدمة المطلوبة...':'Write a brief, clear description of the issue or requested service...','مرفقات الطلب':'Request Attachments','صور المشكلة':'Issue Photos','صور المعدة':'Equipment Photos','تقرير سابق (إن وجد)':'Previous Report (if available)','موعد الزيارة':'Visit Schedule','تاريخ الزيارة المطلوب':'Preferred Visit Date','الوقت المطلوب':'Preferred Time','إرسال طلب الخدمة':'Submit Service Request','إرسال الطلب':'Submit Request','إلغاء':'Cancel','إلغاء الطلب والعودة للرئيسية':'Cancel and Return Home','رقم تذكرة فريد':'Unique Ticket Number',
'✓ بعد تسجيل الطلب بنجاح، يصدر النظام':'✓ After the request is submitted successfully, the system issues','للطلب وفق آلية الترقيم المعتمدة، ويُعرض للعميل في صفحة تأكيد الاستلام.':'for the request using the approved numbering sequence, and displays it on the receipt confirmation page.','بإرسال الطلب، سيتم استخدام بيانات العميل والأصل المسجلة لدى UNIFCO لمعالجة الطلب وربطه بالسجل التشغيلي الصحيح.':'By submitting, the customer and asset data registered with UNIFCO will be used to process the request and link it to the correct operational record.',
'بيانات العميل الجديد':'New Customer Information','اسم الشركة / المنشأة':'Company / Organization Name','اسم المسؤول':'Contact Name','المنطقة':'Region','اختر المنطقة':'Select Region','اختر المدينة':'Select City','الحي':'District','العنوان التفصيلي':'Detailed Address','استخدم موقعي الحالي':'Use My Current Location','يمكن تحديد الموقع لتسريع تسجيل موقع الخدمة.':'Location can be shared to speed up service-site registration.','موقع المنشأة':'Facility Location',
'متى بدأت المشكلة':'When Did the Issue Start?','الآن':'Now','منذ ساعات':'Hours Ago','منذ يوم':'One Day Ago','منذ عدة أيام':'Several Days Ago','حالة المعدة الآن':'Current Equipment Status','تعمل':'Running','تعمل جزئياً':'Partially Running','متوقفة':'Stopped','تأثير العطل على التشغيل':'Operational Impact','محدود':'Limited','توقف جزئي':'Partial Outage','توقف كامل':'Full Outage','هل توجد خطورة؟':'Is There a Safety Risk?','لا توجد':'None','خطورة محتملة':'Potential Risk','خطورة عالية':'High Risk','حالة الموقع':'Site Status','مفتوح ويعمل':'Open and Operating','تشغيل محدود':'Limited Operation','مغلق':'Closed'
};
const translateText=node=>{const raw=node.nodeValue||'',trim=raw.trim();if(!trim||!dictionary[trim])return;node.nodeValue=raw.replace(trim,dictionary[trim])};
const translateElement=el=>{if(el.nodeType!==1)return;['placeholder','title','aria-label'].forEach(a=>{const value=el.getAttribute(a);if(value&&dictionary[value.trim()])el.setAttribute(a,dictionary[value.trim()])});if(el.tagName==='INPUT'&&el.name==='lang')el.value='en'};
const translate=root=>{translateElement(root);const walker=document.createTreeWalker(root,NodeFilter.SHOW_ELEMENT|NodeFilter.SHOW_TEXT);let node;while(node=walker.nextNode()){if(node.nodeType===3)translateText(node);else translateElement(node)}};
document.documentElement.lang='en';document.documentElement.dir='ltr';document.body.dir='ltr';translate(document.body);document.title=dictionary[document.title]||'Request Service | UNIFCO';
new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===3)translateText(node);else if(node.nodeType===1)translate(node)}))).observe(document.body,{childList:true,subtree:true});
})();
</script>
HTML;
            $html = str_replace('</body>', $localization.'</body>', $html);
        }

        $response->setContent($html);

        return $response;
    }
}
