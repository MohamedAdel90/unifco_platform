<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicServiceController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $locale = $request->query('lang', $request->session()->get('public_locale', 'ar'));
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';

        $catalog = $this->catalog();
        abort_unless(isset($catalog[$slug]), 404);

        return view('public.service-detail', [
            'service' => $catalog[$slug],
            'slug' => $slug,
            'locale' => $locale,
            'rtl' => $locale === 'ar',
        ]);
    }

    private function catalog(): array
    {
        return [
            'transformer-maintenance' => $this->service('Transformers','المحولات الكهربائية','/images/home/service-photo-v14-04.webp',
                'Inspection, testing and maintenance programs for power transformers focused on reliability, safety and early fault detection.',
                'برامج فحص واختبار وصيانة المحولات الكهربائية بهدف رفع الاعتمادية والسلامة والكشف المبكر عن الأعطال.',
                ['Visual Inspection','Oil Testing','Insulation Resistance Testing','Winding Resistance','Transformer Ratio Test (TTR)','Protection Testing','Thermal Inspection'],
                ['الفحص البصري','اختبارات الزيت','اختبار مقاومة العزل','قياس مقاومة الملفات','اختبار نسبة التحويل TTR','اختبارات الحماية','الفحص الحراري']),
            'ups-systems' => $this->service('UPS Systems','أنظمة UPS','/images/home/service-photo-v14-04.webp',
                'Critical power support for UPS systems and battery banks to maintain continuity for sensitive and mission-critical loads.',
                'خدمات أنظمة الطاقة غير المنقطعة وبنوك البطاريات لضمان استمرارية الأحمال الحساسة والحرجة.',
                ['UPS Health Check','Battery Capacity Test','Battery Impedance Test','Rectifier & Inverter Inspection','Bypass System Testing','Load Bank Testing','Alarm & Monitoring Verification'],
                ['فحص حالة UPS','اختبار سعة البطاريات','اختبار ممانعة البطاريات','فحص المقوم والعاكس','اختبار نظام Bypass','اختبار Load Bank','فحص الإنذارات والمراقبة']),
            'generators' => $this->service('Generators','المولدات','/images/home/service-photo-v14-02.webp',
                'Planned and corrective maintenance for standby and prime generators to protect power availability during outages and peak demand.',
                'صيانة وقائية وتصحيحية للمولدات الاحتياطية والرئيسية لضمان جاهزية الطاقة عند الانقطاع والأحمال العالية.',
                ['Engine Inspection','Cooling System Check','Fuel System Inspection','Starting Batteries','Alternator Testing','Load Bank Test','Control Panel & Alarms'],
                ['فحص المحرك','فحص نظام التبريد','فحص نظام الوقود','فحص بطاريات التشغيل','اختبار المولد الكهربائي','اختبار Load Bank','فحص لوحة التحكم والإنذارات']),
            'mv-systems' => $this->service('MV Systems','أنظمة الجهد المتوسط','/images/home/service-photo-v14-04.webp',
                'Maintenance and testing of medium-voltage switchgear, protection and distribution equipment for safe and dependable operation.',
                'صيانة واختبار لوحات ومعدات الجهد المتوسط وأنظمة الحماية والتوزيع لضمان تشغيل آمن وموثوق.',
                ['Switchgear Inspection','Circuit Breaker Testing','Protection Relay Testing','Insulation Resistance','Contact Resistance','Cable Testing','Thermal Scanning'],
                ['فحص لوحات الجهد المتوسط','اختبار القواطع','اختبار مرحلات الحماية','اختبار مقاومة العزل','اختبار مقاومة التلامس','اختبار الكابلات','الفحص الحراري']),
            'preventive-maintenance' => $this->service('Preventive Maintenance','الصيانة الوقائية','/images/home/service-photo-v14-01.webp',
                'Structured preventive maintenance plans that reduce failures, extend asset life and improve operational availability.',
                'خطط صيانة وقائية منظمة تقلل الأعطال وتطيل عمر الأصول وترفع الجاهزية التشغيلية.',
                ['Asset Condition Assessment','Scheduled Inspection','Cleaning & Tightening','Electrical Measurements','Lubrication & Adjustment','Consumables Replacement','Maintenance Reporting'],
                ['تقييم حالة الأصول','فحوصات مجدولة','التنظيف والربط','القياسات الكهربائية','التشحيم والضبط','استبدال المواد الاستهلاكية','تقارير الصيانة']),
            'corrective-emergency-maintenance' => $this->service('Corrective / Emergency Maintenance','الصيانة التصحيحية / الطارئة','/images/home/service-photo-v14-01.webp',
                'Rapid fault diagnosis and corrective intervention to restore failed electrical and facility assets safely and efficiently.',
                'تشخيص سريع للأعطال وتنفيذ أعمال الصيانة التصحيحية لإعادة الأصول الكهربائية والمرافق إلى التشغيل بأمان وكفاءة.',
                ['Emergency Response','Fault Diagnosis','Isolation & Safety','Component Repair','Component Replacement','Functional Testing','Root Cause Report'],
                ['الاستجابة الطارئة','تشخيص العطل','العزل وتأمين الموقع','إصلاح المكونات','استبدال المكونات','اختبار التشغيل','تقرير السبب الجذري']),
            'inspection-testing' => $this->service('Inspection & Testing','الفحص والاختبار','/images/home/service-photo-v14-02.webp',
                'Independent inspection, measurement and functional testing to verify equipment condition, safety and compliance.',
                'فحوصات وقياسات واختبارات تشغيل للتحقق من حالة المعدات والسلامة والالتزام بالمتطلبات الفنية.',
                ['Visual Inspection','Insulation Testing','Earth Resistance','Protection Testing','Functional Testing','Thermography','Technical Test Reports'],
                ['الفحص البصري','اختبار العزل','اختبار مقاومة التأريض','اختبارات الحماية','الاختبارات الوظيفية','التصوير الحراري','تقارير الاختبارات الفنية']),
            'maintenance-contracts' => $this->service('Maintenance Contracts','عقود الصيانة','/images/home/service-photo-v14-00.webp',
                'Flexible annual and multi-year maintenance contracts with defined scope, schedules, reporting and service levels.',
                'عقود صيانة سنوية ومتعددة السنوات بنطاق واضح وجداول وتقارير ومستويات خدمة محددة.',
                ['Asset Survey','Maintenance Plan','Preventive Visits','Corrective Callouts','Emergency Coverage','SLA Management','Monthly / Quarterly Reports'],
                ['حصر الأصول','خطة الصيانة','الزيارات الوقائية','البلاغات التصحيحية','تغطية الطوارئ','إدارة SLA','تقارير شهرية / ربع سنوية']),
            'industrial-commercial-electrical' => $this->service('Industrial / Commercial Electrical Services','الخدمات الكهربائية الصناعية / التجارية','/images/home/service-photo-v14-04.webp',
                'Electrical operation, maintenance and technical support for industrial plants, commercial buildings and critical facilities.',
                'خدمات تشغيل وصيانة ودعم فني كهربائي للمصانع والمباني التجارية والمنشآت الحرجة.',
                ['Electrical Distribution','Panels & Switchboards','Power Quality Checks','Cable & Busbar Inspection','Lighting Systems','Earthing Systems','Electrical Troubleshooting'],
                ['أنظمة التوزيع الكهربائي','اللوحات الكهربائية','فحوصات جودة الطاقة','فحص الكابلات والبسبارات','أنظمة الإنارة','أنظمة التأريض','تشخيص الأعطال الكهربائية']),
            'asset-management' => $this->service('Asset Management','إدارة الأصول','/images/home/service-photo-v14-05.webp',
                'Lifecycle-focused asset management combining asset registers, condition, maintenance history and performance visibility.',
                'إدارة دورة حياة الأصول من خلال السجل الفني والحالة وتاريخ الصيانة ومؤشرات الأداء.',
                ['Asset Register','Asset Tagging','Condition Assessment','Maintenance History','Warranty Tracking','Lifecycle Planning','Performance Reporting'],
                ['سجل الأصول','ترقيم وتعريف الأصول','تقييم الحالة','تاريخ الصيانة','متابعة الضمان','تخطيط دورة الحياة','تقارير الأداء']),
            'hvac-systems' => $this->service('HVAC Systems','أنظمة HVAC','/images/home/service-photo-v14-03.webp',
                'HVAC operation and maintenance focused on comfort, reliability, indoor conditions and energy-efficient performance.',
                'تشغيل وصيانة أنظمة التكييف والتهوية بهدف تحقيق الراحة والاعتمادية وكفاءة استهلاك الطاقة.',
                ['Chiller Inspection','AHU / FCU Maintenance','Filter Replacement','Refrigerant Checks','Control & Thermostat Testing','Airflow Measurement','Energy Performance Checks'],
                ['فحص الشيلرات','صيانة AHU / FCU','استبدال الفلاتر','فحص وسيط التبريد','اختبار التحكم والثرموستات','قياس تدفق الهواء','فحص كفاءة الطاقة']),
            'mep-services' => $this->service('MEP Services','خدمات MEP','/images/home/service-photo-v14-02.webp',
                'Integrated mechanical, electrical and plumbing maintenance for buildings and facilities through one coordinated technical team.',
                'صيانة متكاملة للأنظمة الميكانيكية والكهربائية والصحية للمباني والمنشآت من خلال فريق فني موحد.',
                ['Electrical Systems','HVAC & Ventilation','Pumps & Mechanical Equipment','Plumbing Systems','Fire & Life Safety Interfaces','Testing & Commissioning','MEP Preventive Maintenance'],
                ['الأنظمة الكهربائية','التكييف والتهوية','المضخات والمعدات الميكانيكية','أنظمة السباكة','واجهات أنظمة الحريق والسلامة','الاختبارات والتشغيل','الصيانة الوقائية لـ MEP']),
            'facility-management' => $this->service('Facility Management','إدارة المرافق','/images/home/service-photo-v14-00.webp',
                'Integrated facility operations combining technical maintenance, service coordination, asset visibility and performance management.',
                'إدارة متكاملة للمرافق تجمع التشغيل والصيانة الفنية وتنسيق الخدمات وإدارة الأصول ومتابعة الأداء.',
                ['Facility Operations','Technical Maintenance','Helpdesk & Work Orders','Vendor Coordination','Asset Management','SLA & KPI Monitoring','Operational Reporting'],
                ['تشغيل المرافق','الصيانة الفنية','مركز البلاغات وأوامر العمل','تنسيق المقاولين','إدارة الأصول','متابعة SLA وKPI','التقارير التشغيلية']),
        ];
    }

    private function service(string $en, string $ar, string $image, string $overviewEn, string $overviewAr, array $technicalEn, array $technicalAr): array
    {
        return compact('en','ar','image','overviewEn','overviewAr','technicalEn','technicalAr') + [
            'supportEn' => ['Preventive Maintenance','Corrective Maintenance','Testing','Inspection','Emergency Support'],
            'supportAr' => ['الصيانة الوقائية','الصيانة التصحيحية','الاختبارات','الفحص','الدعم الطارئ'],
        ];
    }
}
