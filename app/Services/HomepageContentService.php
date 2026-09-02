<?php

namespace App\Services;

use App\Models\HomepageClient;
use App\Models\HomepageProject;
use App\Models\HomepageSection;
use Illuminate\Support\Facades\Cache;

class HomepageContentService
{
    private const SECTIONS = [
        'hero', 'capabilities', 'about', 'services', 'process',
        'industries', 'operations', 'why', 'showcase', 'clients',
        'emergency', 'footer_cta', 'footer',
    ];

    public function getContent(string $locale): array
    {
        $lang = $locale === 'ar' ? 'ar' : 'en';
        $dir = $lang === 'ar' ? 'rtl' : 'ltr';

        $defaults = $this->hardcodedDefaults($lang);
        $db = Cache::remember("homepage_content_{$lang}", 3600, fn () => $this->loadFromDb($lang));

        $home = array_merge($defaults, $db);

        $home['lang'] = $lang;
        $home['dir'] = $dir;
        $home['language'] = $lang === 'ar' ? 'EN' : 'AR';

        $projects = Cache::remember("homepage_projects_{$lang}", 3600, fn () => $this->loadProjects($lang));
        $clients = Cache::remember("homepage_clients_{$lang}", 3600, fn () => $this->loadClients($lang));

        $home['showcase_projects'] = $projects ?: $this->defaultShowcaseProjects($lang);
        $home['showcase_clients'] = $clients ?: $this->defaultShowcaseClients($lang);

        return $home;
    }

    private function loadFromDb(string $locale): array
    {
        try {
            $sections = HomepageSection::active()->ordered()->get();
        } catch (\Throwable) {
            return [];
        }

        $data = [];

        foreach ($sections as $section) {
            $sectionData = $section->getData($locale);
            if (empty($sectionData)) {
                continue;
            }
            $data = array_merge($data, $sectionData);
        }

        return $data;
    }

    private function loadProjects(string $locale): array
    {
        try {
            return HomepageProject::active()->ordered()->get()
                ->map(fn (HomepageProject $p) => $p->toArrayForLocale($locale))
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function loadClients(string $locale): array
    {
        try {
            return HomepageClient::active()->ordered()->get()
                ->map(fn (HomepageClient $c) => $c->toArrayForLocale($locale))
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function clearAllCache(): void
    {
        HomepageSection::clearCache();
        HomepageProject::clearCache();
        HomepageClient::clearCache();
    }

    private function hardcodedDefaults(string $lang): array
    {
        if ($lang === 'ar') {
            return $this->arabicDefaults();
        }

        return $this->englishDefaults();
    }

    private function defaultShowcaseProjects(string $lang): array
    {
        if ($lang === 'ar') {
            return [
                ['image' => '/images/home/projects/ats-maintenance.webp', 'year' => '2024-2025', 'title' => 'صيانة المفاتيح الكهربائية ATS', 'owner' => 'شركة المياه الوطنية', 'location' => 'المدينة المنورة', 'scope' => 'صيانة وقائية وتصحيحية'],
                ['image' => '/images/home/projects/generator-maintenance.webp', 'year' => '2025-2026', 'title' => 'صيانة مولد مبنى المستشفى', 'owner' => 'مستشفى المدينة الوطني', 'location' => 'المدينة المنورة', 'scope' => 'صيانة وقائية وتصحيحية'],
                ['image' => '/images/home/projects/transformer-inspection.webp', 'year' => '2024-2025', 'title' => 'صيانة المحولات ولوحات التوزيع', 'owner' => 'وزارة الموارد البشرية', 'location' => 'تبوك', 'scope' => 'صيانة وقائية وتصحيحية'],
                ['image' => '/images/home/projects/transformer-field.webp', 'year' => '2026', 'title' => 'توريد وتركيب محولات', 'owner' => 'مستشفى القطيف المركزي', 'location' => 'القطيف', 'scope' => 'توريد وتركيب واختبار'],
                ['image' => '/images/home/projects/transformer-oil-service.webp', 'year' => '2025', 'title' => 'فلترة زيت المحولات', 'owner' => 'مركز المعلومات STC', 'location' => 'مكة المكرمة', 'scope' => 'إصلاح وتركيب قطع غيار'],
                ['image' => '/images/home/projects/hvac-maintenance.webp', 'year' => '2024', 'title' => 'أعمال صيانة للتشيلرات', 'owner' => 'سفارة دولة الإمارات العربية المتحدة', 'location' => 'الرياض', 'scope' => 'صيانة وقائية وقطع غيار'],
                ['image' => '/images/home/projects/emergency-team.webp', 'year' => '2024-2026', 'title' => 'صيانة المولدات', 'owner' => 'شركة المياه الوطنية', 'location' => 'القصيم', 'scope' => 'صيانة وقائية وتصحيحية'],
                ['image' => '/images/home/projects/electrical-testing.webp', 'year' => '2024-2025', 'title' => 'صيانة المولدات الاحتياطية', 'owner' => 'شركة المياه الوطنية', 'location' => 'تبوك', 'scope' => 'فحص وصيانة وتشغيل'],
                ['image' => '/images/home/projects/site-response.webp', 'year' => '2025', 'title' => 'صيانة مولد قوة الأمن الخاصة', 'owner' => 'قوة الأمن الخاصة الرابعة', 'location' => 'المدينة المنورة', 'scope' => 'إصلاح وتركيب قطع غيار'],
                ['image' => '/images/home/projects/chiller-service.webp', 'year' => '2025', 'title' => 'إنشاء غرف تبريد', 'owner' => 'شركة المراعي', 'location' => 'حفر الباطن', 'scope' => 'توريد وتركيب واختبار'],
            ];
        }

        return [
            ['image' => '/images/home/projects/ats-maintenance.webp', 'year' => '2024-2025', 'title' => 'ATS switch maintenance', 'owner' => 'National Water Company', 'location' => 'Madinah', 'scope' => 'Preventive and corrective maintenance'],
            ['image' => '/images/home/projects/generator-maintenance.webp', 'year' => '2025-2026', 'title' => 'Hospital generator maintenance', 'owner' => 'Madinah National Hospital', 'location' => 'Madinah', 'scope' => 'Preventive and corrective maintenance'],
            ['image' => '/images/home/projects/transformer-inspection.webp', 'year' => '2024-2025', 'title' => 'Transformer and distribution panel maintenance', 'owner' => 'Ministry of Human Resources', 'location' => 'Tabuk', 'scope' => 'Preventive and corrective maintenance'],
            ['image' => '/images/home/projects/transformer-field.webp', 'year' => '2026', 'title' => 'Transformer supply and installation', 'owner' => 'Qatif Central Hospital', 'location' => 'Qatif', 'scope' => 'Supply, installation and testing'],
            ['image' => '/images/home/projects/transformer-oil-service.webp', 'year' => '2025', 'title' => 'Transformer oil filtration', 'owner' => 'STC Information Center', 'location' => 'Makkah', 'scope' => 'Repair and spare-parts installation'],
            ['image' => '/images/home/projects/hvac-maintenance.webp', 'year' => '2024', 'title' => 'Chiller maintenance works', 'owner' => 'UAE Embassy', 'location' => 'Riyadh', 'scope' => 'Preventive maintenance and spare parts'],
            ['image' => '/images/home/projects/emergency-team.webp', 'year' => '2024-2026', 'title' => 'Generator maintenance', 'owner' => 'National Water Company', 'location' => 'Qassim', 'scope' => 'Preventive and corrective maintenance'],
            ['image' => '/images/home/projects/electrical-testing.webp', 'year' => '2024-2025', 'title' => 'Standby generator maintenance', 'owner' => 'National Water Company', 'location' => 'Tabuk', 'scope' => 'Inspection, maintenance and operation'],
            ['image' => '/images/home/projects/site-response.webp', 'year' => '2025', 'title' => 'Special Security Force generator maintenance', 'owner' => 'Fourth Special Security Force', 'location' => 'Madinah', 'scope' => 'Repair and spare-parts installation'],
            ['image' => '/images/home/projects/chiller-service.webp', 'year' => '2025', 'title' => 'Cold-room construction', 'owner' => 'Almarai', 'location' => 'Hafar Al Batin', 'scope' => 'Supply, installation and testing'],
        ];
    }

    private function defaultShowcaseClients(string $lang): array
    {
        if ($lang === 'ar') {
            return [
                ['/images/home/clients/nwc.webp', 'شركة المياه الوطنية'],
                ['/images/home/clients/hrsd.webp', 'وزارة الموارد البشرية والتنمية الاجتماعية'],
                ['/images/home/clients/islamic-university.webp', 'الجامعة الإسلامية بالمدينة المنورة'],
                ['/images/home/clients/ministry-health.webp', 'وزارة الصحة'],
                ['/images/home/clients/uae-embassy.webp', 'سفارة دولة الإمارات العربية المتحدة'],
                ['/images/home/clients/stc.webp', 'STC'],
                ['/images/home/clients/almarai.webp', 'شركة المراعي'],
                ['/images/home/clients/sdb.webp', 'بنك التنمية الاجتماعية'],
            ];
        }

        return [
            ['/images/home/clients/nwc.webp', 'National Water Company'],
            ['/images/home/clients/hrsd.webp', 'Ministry of Human Resources and Social Development'],
            ['/images/home/clients/islamic-university.webp', 'Islamic University of Madinah'],
            ['/images/home/clients/ministry-health.webp', 'Ministry of Health'],
            ['/images/home/clients/uae-embassy.webp', 'Embassy of the United Arab Emirates'],
            ['/images/home/clients/stc.webp', 'STC'],
            ['/images/home/clients/almarai.webp', 'Almarai'],
            ['/images/home/clients/sdb.webp', 'Social Development Bank'],
        ];
    }

    private function arabicDefaults(): array
    {
        return [
            'nav' => ['الرئيسية', 'من نحن', 'الخدمات', 'عملاؤنا', 'المشاريع', 'الوظائف', 'تواصل معنا'],
            'login' => 'تسجيل الدخول',
            'request' => 'طلب خدمة',
            'hero_eyebrow' => 'ONE FACILITY SHOP',
            'hero_title' => 'شريك واحد لجميع احتياجات منشأتك',
            'hero_text' => 'حلول متكاملة للمرافق والمشاريع والتشغيل والصيانة',
            'explore' => 'استكشف خدماتنا',
            'hero_proofs' => [['clock', 'دعم وتشغيل', '24/7'], ['shield', 'صيانة وقائية', 'وتصحيحية'], ['chart', 'متابعة رقمية', 'وتقارير شفافة']],
            'capabilities' => [
                ['settings', 'Electrical Power Systems', 'محولات · UPS · مولدات · MV'],
                ['shield', 'Maintenance Services', 'وقائية · تصحيحية · طوارئ'],
                ['building', 'Facility & MEP Services', 'إدارة مرافق وخدمات فنية'],
                ['monitor', 'Asset & Digital Management', 'إدارة أصول ومتابعة رقمية'],
            ],
            'about_kicker' => 'من نحن',
            'about_title' => 'شريكك المتكامل للأنظمة الكهربائية وإدارة المرافق',
            'about_text' => 'تقدم UNIFCO خدمات متخصصة في أنظمة الطاقة الكهربائية والصيانة الوقائية والتصحيحية والفحوصات والعقود، إلى جانب خدمات MEP وHVAC وإدارة المرافق والأصول، من خلال فرق فنية مؤهلة وعمليات منظمة ومتابعة رقمية.',
            'about_points' => [
                ['target', 'إدارة مركزية', 'للأصول والصيانة'], ['team', 'فريق واحد', 'لخدمات متعددة'],
                ['report', 'متابعة دورية', 'وتقارير شفافة'], ['layers', 'حلول مصممة', 'لكل منشأة'],
            ],
            'about_button' => 'تعرّف على UNIFCO',
            'stats' => [['+', 'المواقع المخدومة'], ['+', 'الأصول المدارة'], ['98%', 'الالتزام بالـ SLA'], ['24/7', 'دعم العمليات']],
            'services_kicker' => 'خدماتنا',
            'services_title' => 'حلول متكاملة تحت سقف واحد',
            'services_text' => 'من أنظمة الطاقة الحرجة والصيانة المتخصصة إلى إدارة الأصول والمرافق',
            'services' => [
                ['01', '/images/home/service-photo-v14-04.webp', 'Transformers · المحولات الكهربائية', 'فحص وصيانة وتشغيل المحولات الكهربائية ورفع الاعتمادية وكفاءة التشغيل.'],
                ['02', '/images/home/ats.svg', 'UPS Systems', 'توريد وفحص وصيانة أنظمة UPS والطاقة غير المنقطعة للأنظمة الحرجة.'],
                ['03', '/images/home/generator-maintenance-card.svg', 'Generators · المولدات', 'صيانة وفحص وتشغيل المولدات وأنظمة القدرة الاحتياطية ودعم استمرارية الطاقة.'],
                ['04', '/images/home/facility-power.svg', 'MV Systems · أنظمة الجهد المتوسط', 'خدمات أنظمة الجهد المتوسط واللوحات والحماية والاختبارات والتشغيل.'],
                ['05', '/images/home/service-photo-v14-01.webp', 'Preventive Maintenance', 'برامج صيانة وقائية دورية تقلل الأعطال وتحسن جاهزية الأصول والمعدات.'],
                ['06', '/images/home/about-technician-v14.webp', 'Corrective / Emergency Maintenance', 'استجابة للأعطال والصيانة التصحيحية والطارئة لإعادة التشغيل بأسرع وقت ممكن.'],
                ['07', '/images/home/service-photo-v14-02.webp', 'Inspection & Testing', 'فحص واختبار الأنظمة والمعدات الكهربائية وإصدار تقارير فنية واضحة.'],
                ['08', '/images/home/industry-photo-v14-01.webp', 'Maintenance Contracts', 'عقود صيانة دورية بمستويات خدمة وجداول واضحة ومتابعة وتقارير مستمرة.'],
                ['09', '/images/home/industry-photo-v14-04.webp', 'Industrial / Commercial Electrical Services', 'خدمات كهربائية متخصصة للمنشآت الصناعية والتجارية والمواقع التشغيلية.'],
                ['10', '/images/home/service-photo-v14-05.webp', 'Asset Management', 'إدارة دورة حياة الأصول وسجلات الصيانة والضمانات والتاريخ الفني.'],
                ['11', '/images/home/service-photo-v14-03.webp', 'HVAC Systems', 'فحص وصيانة وتشغيل أنظمة التكييف والتهوية وتحسين كفاءة الأداء.'],
                ['12', '/images/home/industry-photo-v14-03.webp', 'MEP Services', 'صيانة وتشغيل الأنظمة الميكانيكية والكهربائية والصحية للمباني والمنشآت.'],
                ['13', '/images/home/service-photo-v14-00.webp', 'Facility Management', 'إدارة وتشغيل المرافق والخدمات اليومية ومتابعة الأصول والمقاولين والأداء.'],
            ],
            'more' => 'المزيد',
            'all_services' => 'عرض جميع الخدمات',
            'process_kicker' => 'كيف نعمل',
            'process_title' => 'طريقة عمل واضحة من البداية إلى النهاية -- من التقييم إلى التحسين',
            'process' => [
                ['01', 'التقييم', 'فهم الموقع والأصول والمخاطر ومتطلبات الخدمة.'],
                ['02', 'التخطيط', 'إعداد خطة العمل والجداول والموارد ومستويات الخدمة.'],
                ['03', 'التنفيذ', 'تنفيذ الأعمال وتسجيل القراءات والنتائج والملاحظات.'],
                ['04', 'المتابعة والتحسين', 'تقارير ومؤشرات وتحسين مستمر للأداء التشغيلي.'],
            ],
            'industries_title' => 'القطاعات التي نخدمها',
            'industries' => [
                ['/images/home/industry-photo-v14-00.webp', 'المباني التجارية'],
                ['/images/home/industry-photo-v14-01.webp', 'المكاتب'],
                ['/images/home/industry-photo-v14-02.webp', 'المرافق الصحية'],
                ['/images/home/industry-photo-v14-03.webp', 'المستودعات والخدمات اللوجستية'],
                ['/images/home/industry-photo-v14-04.webp', 'القطاع الصناعي'],
                ['/images/home/industry-photo-v14-05.webp', 'مرافق الضيافة'],
                ['/images/home/industry-photo-v14-06.webp', 'الجهات الحكومية'],
            ],
            'all_industries' => 'عرض جميع القطاعات',
            'maintenance_title' => 'من الصيانة التفاعلية إلى التشغيل المخطط',
            'maintenance_text' => 'يقلل النظام الوقائي استجابة UNIFCO للأعطال عبر التخطيط للصيانة قبل حدوث المشكلة.',
            'maintenance_checks' => ['خطط صيانة وقائية', 'جداول فحص دورية', 'تواريخ وقطع غيار', 'برامج رفع الكفاءة', 'توثيق أوامر العمل', 'تحسين الأداء المستمر'],
            'maintenance_button' => 'تعرّف على خدمات الصيانة',
            'portal_title' => 'إدارة خدماتك من مكان واحد',
            'portal_text' => 'منصة رقمية متكاملة تتيح لك متابعة جميع خدمات منشأتك بسهولة.',
            'portal_checks' => ['متابعة العقود', 'إدارة الأصول', 'أوامر الصيانة', 'التقارير والمدفوعات', 'الصيانة القادمة', 'مؤشرات الأداء (KPI)'],
            'portal_button' => 'دخول حساب العميل',
            'why_title' => 'لماذا UNIFCO؟',
            'why' => [
                ['layers', 'حلول متكاملة', 'كل خدماتك وأصولك في منصة واحدة'],
                ['report', 'تقارير شفافة ودقيقة', 'تقارير مفصلة لتعزيز الثقة والوضوح'],
                ['clock', 'استجابة سريعة', 'إدارة فعالة للأعطال والطوارئ'],
                ['team', 'فريق مؤهل ومتخصص', 'مهندسون وفنيون بخبرة ومؤهلات عالية'],
                ['settings', 'نهج وقائي ذكي', 'تركيز على منع الأعطال بخطط مدروسة'],
                ['target', 'تجربة موحدة', 'حلول متكاملة من جهة واحدة'],
            ],
            'showcase_kicker' => 'مشاريعنا',
            'showcase_title' => 'خبرة موثوقة في تنفيذ المشاريع',
            'showcase_text' => 'نفذنا مجموعة واسعة من المشاريع والتعميدات في قطاعات متعددة ومناطق مختلفة بالمملكة بجودة وكفاءة.',
            'showcase_metrics' => [
                ['pin', 'مناطق متعددة', 'في المملكة'],
                ['calendar', '2024 - 2026', 'خبرة تنفيذ موثقة'],
                ['team', '24+', 'جهة مالكة'],
                ['report', '44+', 'مشروعًا وتعميدًا'],
            ],
            'carousel_previous' => 'المشاريع السابقة',
            'carousel_next' => 'المشاريع التالية',
            'projects_title' => 'أعمالنا على أرض الواقع',
            'projects_button' => 'عرض جميع المشاريع',
            'clients_title' => 'عملاؤنا وشركاء النجاح',
            'clients_text' => 'نفخر بشراكاتنا مع جهات حكومية وخاصة رائدة في مختلف القطاعات.',
            'more_clients' => 'المزيد من الجهات',
            'all_clients' => 'عرض جميع العملاء',
            'emergency_title' => 'تحتاج إلى تدخل عاجل؟',
            'emergency_text' => 'إذا كان لديك عطل يؤثر على التشغيل، أرسل طلب صيانة طارئة وسيتم التعامل معه مباشرة.',
            'emergency_button' => 'طلب صيانة طارئة',
            'emergency_contact' => 'دعم العمليات 24/7 · أرسل طلبك العاجل مباشرة',
            'emergency_photo_alt' => 'فريق UNIFCO في أحد مواقع العمل',
            'operations_support' => 'دعم العمليات',
            'contact_now' => 'اتصل بنا الآن',
            'email_us' => 'راسلنا',
            'final_title' => 'منشأتك تستحق إدارة أفضل',
            'final_text' => 'دع UNIFCO تتولى التشغيل والصيانة، بينما تركز أنت على أعمالك.',
            'quote' => 'اطلب عرض سعر',
            'contact' => 'تحدث معنا',
            'footer_about' => 'حلول الأنظمة الكهربائية والطاقة والصيانة وإدارة المرافق والأصول عبر تجربة خدمة واحدة متكاملة.',
            'company' => 'الشركة',
            'services_label' => 'الخدمات',
            'contact_label' => 'تواصل معنا',
            'footer_contact' => ['دعم العمليات على مدار الساعة', 'طلبات الخدمة والاستجابة العاجلة', 'المملكة العربية السعودية'],
        ];
    }

    private function englishDefaults(): array
    {
        return [
            'nav' => ['Home', 'About', 'Services', 'Industries', 'How We Work', 'Projects', 'Contact'],
            'login' => 'Sign In',
            'request' => 'Request Service',
            'hero_eyebrow' => 'ONE FACILITY SHOP',
            'hero_title' => 'One partner for every facility need',
            'hero_text' => 'Integrated Facility, Projects, Operations & Maintenance Solutions',
            'explore' => 'Explore Our Services',
            'hero_proofs' => [['clock', 'Operations Support', '24/7'], ['shield', 'Preventive', '& Corrective'], ['chart', 'Digital Tracking', '& Clear Reports']],
            'capabilities' => [
                ['settings', 'Electrical Power Systems', 'Transformers · UPS · Generators · MV'],
                ['shield', 'Maintenance Services', 'Preventive · Corrective · Emergency'],
                ['building', 'Facility & MEP Services', 'Facility operations and technical services'],
                ['monitor', 'Asset & Digital Management', 'Asset lifecycle and digital visibility'],
            ],
            'about_kicker' => 'ABOUT US',
            'about_title' => 'Your integrated electrical and facility services partner',
            'about_text' => 'UNIFCO provides specialist electrical power, preventive and corrective maintenance, testing, maintenance contracts, MEP, HVAC, facility management and asset management services through qualified technical teams, structured operations and digital visibility.',
            'about_points' => [['target', 'Central Management', 'For assets and maintenance'], ['team', 'One Team', 'For multiple services'], ['report', 'Routine Tracking', 'With transparent reports'], ['layers', 'Tailored Solutions', 'For every facility']],
            'about_button' => 'Discover UNIFCO',
            'stats' => [['+', 'Sites Served'], ['+', 'Assets Managed'], ['98%', 'SLA Compliance'], ['24/7', 'Operations Support']],
            'services_kicker' => '',
            'services_title' => 'Our Services',
            'services_text' => 'From critical power systems and specialist maintenance to asset and facility management',
            'services' => [
                ['01', '/images/home/service-photo-v14-04.webp', 'Transformers', 'Inspection, maintenance and operation of electrical transformers to improve reliability and performance.'],
                ['02', '/images/home/service-photo-v14-04.webp', 'UPS Systems', 'Supply, inspection and maintenance of UPS and uninterrupted power systems for critical loads.'],
                ['03', '/images/home/service-photo-v14-02.webp', 'Generators', 'Generator maintenance, testing and operation for reliable standby power continuity.'],
                ['04', '/images/home/service-photo-v14-04.webp', 'MV Systems', 'Medium-voltage systems, switchgear, protection, testing and commissioning services.'],
                ['05', '/images/home/service-photo-v14-01.webp', 'Preventive Maintenance', 'Planned preventive maintenance programs that reduce failures and improve asset readiness.'],
                ['06', '/images/home/service-photo-v14-01.webp', 'Corrective / Emergency Maintenance', 'Responsive corrective and emergency maintenance to restore operation quickly and safely.'],
                ['07', '/images/home/service-photo-v14-02.webp', 'Inspection & Testing', 'Electrical equipment and system inspection, testing and clear technical reporting.'],
                ['08', '/images/home/service-photo-v14-00.webp', 'Maintenance Contracts', 'Structured maintenance contracts with service levels, schedules, tracking and reporting.'],
                ['09', '/images/home/service-photo-v14-04.webp', 'Industrial / Commercial Electrical Services', 'Specialist electrical services for industrial plants, commercial buildings and operating sites.'],
                ['10', '/images/home/service-photo-v14-05.webp', 'Asset Management', 'Complete asset lifecycle, maintenance history, warranty and technical record management.'],
                ['11', '/images/home/service-photo-v14-03.webp', 'HVAC Systems', 'Inspection, maintenance and operation of HVAC systems to improve efficiency and reliability.'],
                ['12', '/images/home/service-photo-v14-02.webp', 'MEP Services', 'Mechanical, electrical and plumbing systems maintenance and operations for buildings and facilities.'],
                ['13', '/images/home/service-photo-v14-00.webp', 'Facility Management', 'Integrated facility operations, daily services, asset oversight and contractor performance management.'],
            ],
            'more' => 'Learn more',
            'all_services' => 'View All Services',
            'process_kicker' => 'HOW WE WORK',
            'process_title' => 'A clear process from start to finish',
            'process' => [
                ['01', 'Assessment', 'Understand the site, assets, risks and service requirements.'],
                ['02', 'Planning', 'Build the work plan, schedule, resources and service levels.'],
                ['03', 'Execution', 'Deliver the work and document readings, results and findings.'],
                ['04', 'Follow-up & Improvement', 'Report performance and continuously improve operations.'],
            ],
            'industries_title' => 'Industries we serve',
            'industries' => [
                ['/images/home/industry-photo-v14-00.webp', 'Commercial Buildings'],
                ['/images/home/industry-photo-v14-01.webp', 'Offices'],
                ['/images/home/industry-photo-v14-02.webp', 'Healthcare Facilities'],
                ['/images/home/industry-photo-v14-03.webp', 'Warehouses & Logistics'],
                ['/images/home/industry-photo-v14-04.webp', 'Industrial Facilities'],
                ['/images/home/industry-photo-v14-05.webp', 'Hospitality'],
                ['/images/home/industry-photo-v14-06.webp', 'Government'],
            ],
            'all_industries' => 'View All Industries',
            'maintenance_title' => 'From reactive maintenance to planned operations',
            'maintenance_text' => 'UNIFCO reduces emergency failures by planning, documenting and improving maintenance before problems interrupt your operation.',
            'maintenance_checks' => ['Preventive plans', 'Routine inspections', 'History and spare parts', 'Efficiency programs', 'Work order documentation', 'Continuous improvement'],
            'maintenance_button' => 'Explore Maintenance Services',
            'portal_title' => 'Manage every service in one place',
            'portal_text' => 'A connected client portal that makes every facility service easy to monitor.',
            'portal_checks' => ['Contract tracking', 'Asset management', 'Maintenance requests', 'Reports and payments', 'Upcoming maintenance', 'SLA performance'],
            'portal_button' => 'Client Portal Login',
            'why_title' => 'Why UNIFCO?',
            'why' => [
                ['layers', 'Integrated Solutions', 'All services and assets in one platform'],
                ['report', 'Clear Reporting', 'Accurate reports that build visibility and trust'],
                ['clock', 'Fast Response', 'Structured handling for failures and emergencies'],
                ['team', 'Qualified Teams', 'Experienced engineers and skilled technicians'],
                ['settings', 'Smart Prevention', 'Maintenance plans focused on avoiding failures'],
                ['target', 'One Experience', 'A complete solution from one accountable partner'],
            ],
            'showcase_kicker' => 'OUR PROJECTS',
            'showcase_title' => 'Proven experience in project delivery',
            'showcase_text' => 'A broad portfolio of maintenance, supply and installation assignments delivered across sectors and regions of Saudi Arabia.',
            'showcase_metrics' => [['pin', 'Multiple regions', 'Across Saudi Arabia'], ['calendar', '2024 - 2026', 'Documented delivery'], ['team', '24+', 'Client organizations'], ['report', '44+', 'Projects and assignments']],
            'carousel_previous' => 'Previous items',
            'carousel_next' => 'Next items',
            'projects_title' => 'Our work in action',
            'projects_button' => 'View All Projects',
            'clients_title' => 'Our Clients and Success Partners',
            'clients_text' => 'We are proud to support leading government and private-sector organizations across multiple industries.',
            'more_clients' => 'More organizations',
            'all_clients' => 'View All Clients',
            'emergency_title' => 'Need urgent support?',
            'emergency_text' => 'If a failure is affecting operations, send an emergency maintenance request for immediate routing.',
            'emergency_button' => 'Emergency Maintenance',
            'emergency_contact' => '24/7 operations support · Send your urgent request directly',
            'emergency_photo_alt' => 'UNIFCO field team at an operating site',
            'operations_support' => 'Operations support',
            'contact_now' => 'Call us now',
            'email_us' => 'Email us',
            'final_title' => 'Your facility deserves better management',
            'final_text' => 'Let UNIFCO manage operations and maintenance while you focus on your business.',
            'quote' => 'Request a Quote',
            'contact' => 'Talk to Us',
            'footer_about' => 'Integrated electrical power, maintenance, facility and asset management through one connected service experience.',
            'company' => 'Company',
            'services_label' => 'Services',
            'contact_label' => 'Contact',
            'footer_contact' => ['24/7 operations support', 'Service requests and urgent response', 'Saudi Arabia'],
        ];
    }
}
