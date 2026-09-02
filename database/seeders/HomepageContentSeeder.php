<?php

namespace Database\Seeders;

use App\Models\HomepageClient;
use App\Models\HomepageProject;
use App\Models\HomepageSection;
use Illuminate\Database\Seeder;

class HomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSections();
        $this->seedProjects();
        $this->seedClients();
    }

    private function seedSections(): void
    {
        $sections = [
            0 => ['hero', [
                'eyebrow' => 'ONE FACILITY SHOP',
                'title_ar' => 'شريك واحد لجميع احتياجات منشأتك',
                'title_en' => 'One partner for every facility need',
                'text_ar' => 'حلول متكاملة للمرافق والمشاريع والتشغيل والصيانة',
                'text_en' => 'Integrated Facility, Projects, Operations & Maintenance Solutions',
                'button_ar' => 'استكشف خدماتنا',
                'button_en' => 'Explore Our Services',
                'proofs' => [
                    ['icon' => 'clock', 'label_ar' => 'دعم وتشغيل', 'label_en' => 'Operations Support', 'sub_ar' => '24/7', 'sub_en' => '24/7'],
                    ['icon' => 'shield', 'label_ar' => 'صيانة وقائية', 'label_en' => 'Preventive', 'sub_ar' => 'وتصحيحية', 'sub_en' => '& Corrective'],
                    ['icon' => 'chart', 'label_ar' => 'متابعة رقمية', 'label_en' => 'Digital Tracking', 'sub_ar' => 'وتقارير شفافة', 'sub_en' => '& Clear Reports'],
                ],
            ]],
            10 => ['capabilities', [
                'items' => [
                    ['icon' => 'settings', 'title_ar' => 'Electrical Power Systems', 'title_en' => 'Electrical Power Systems', 'subtitle_ar' => 'محولات · UPS · مولدات · MV', 'subtitle_en' => 'Transformers · UPS · Generators · MV'],
                    ['icon' => 'shield', 'title_ar' => 'Maintenance Services', 'title_en' => 'Maintenance Services', 'subtitle_ar' => 'وقائية · تصحيحية · طوارئ', 'subtitle_en' => 'Preventive · Corrective · Emergency'],
                    ['icon' => 'building', 'title_ar' => 'Facility & MEP Services', 'title_en' => 'Facility & MEP Services', 'subtitle_ar' => 'إدارة مرافق وخدمات فنية', 'subtitle_en' => 'Facility operations and technical services'],
                    ['icon' => 'monitor', 'title_ar' => 'Asset & Digital Management', 'title_en' => 'Asset & Digital Management', 'subtitle_ar' => 'إدارة أصول ومتابعة رقمية', 'subtitle_en' => 'Asset lifecycle and digital visibility'],
                ],
            ]],
            20 => ['about', [
                'kicker_ar' => 'من نحن', 'kicker_en' => 'ABOUT US',
                'title_ar' => 'شريكك المتكامل للأنظمة الكهربائية وإدارة المرافق',
                'title_en' => 'Your integrated electrical and facility services partner',
                'text_ar' => 'تقدم UNIFCO خدمات متخصصة في أنظمة الطاقة الكهربائية والصيانة الوقائية والتصحيحية والفحوصات والعقود، إلى جانب خدمات MEP وHVAC وإدارة المرافق والأصول، من خلال فرق فنية مؤهلة وعمليات منظمة ومتابعة رقمية.',
                'text_en' => 'UNIFCO provides specialist electrical power, preventive and corrective maintenance, testing, maintenance contracts, MEP, HVAC, facility management and asset management services through qualified technical teams, structured operations and digital visibility.',
                'button_ar' => 'تعرّف على UNIFCO', 'button_en' => 'Discover UNIFCO',
                'points' => [
                    ['icon' => 'target', 'title_ar' => 'إدارة مركزية', 'title_en' => 'Central Management', 'sub_ar' => 'للأصول والصيانة', 'sub_en' => 'For assets and maintenance'],
                    ['icon' => 'team', 'title_ar' => 'فريق واحد', 'title_en' => 'One Team', 'sub_ar' => 'لخدمات متعددة', 'sub_en' => 'For multiple services'],
                    ['icon' => 'report', 'title_ar' => 'متابعة دورية', 'title_en' => 'Routine Tracking', 'sub_ar' => 'وتقارير شفافة', 'sub_en' => 'With transparent reports'],
                    ['icon' => 'layers', 'title_ar' => 'حلول مصممة', 'title_en' => 'Tailored Solutions', 'sub_ar' => 'لكل منشأة', 'sub_en' => 'For every facility'],
                ],
                'stats' => [
                    ['value_ar' => '+', 'value_en' => '+', 'label_ar' => 'المواقع المخدومة', 'label_en' => 'Sites Served'],
                    ['value_ar' => '+', 'value_en' => '+', 'label_ar' => 'الأصول المدارة', 'label_en' => 'Assets Managed'],
                    ['value_ar' => '98%', 'value_en' => '98%', 'label_ar' => 'الالتزام بالـ SLA', 'label_en' => 'SLA Compliance'],
                    ['value_ar' => '24/7', 'value_en' => '24/7', 'label_ar' => 'دعم العمليات', 'label_en' => 'Operations Support'],
                ],
            ]],
            30 => ['services', [
                'kicker_ar' => 'خدماتنا', 'kicker_en' => '',
                'title_ar' => 'حلول متكاملة تحت سقف واحد',
                'title_en' => 'Our Services',
                'text_ar' => 'من أنظمة الطاقة الحرجة والصيانة المتخصصة إلى إدارة الأصول والمرافق',
                'text_en' => 'From critical power systems and specialist maintenance to asset and facility management',
                'more_ar' => 'المزيد', 'more_en' => 'Learn more',
                'button_ar' => 'عرض جميع الخدمات', 'button_en' => 'View All Services',
                'items' => [
                    ['number' => '01', 'image' => '/images/home/service-photo-v14-04.webp', 'title_ar' => 'Transformers · المحولات الكهربائية', 'title_en' => 'Transformers', 'desc_ar' => 'فحص وصيانة وتشغيل المحولات الكهربائية ورفع الاعتمادية وكفاءة التشغيل.', 'desc_en' => 'Inspection, maintenance and operation of electrical transformers to improve reliability and performance.'],
                    ['number' => '02', 'image' => '/images/home/ats.svg', 'title_ar' => 'UPS Systems', 'title_en' => 'UPS Systems', 'desc_ar' => 'توريد وفحص وصيانة أنظمة UPS والطاقة غير المنقطعة للأنظمة الحرجة.', 'desc_en' => 'Supply, inspection and maintenance of UPS and uninterrupted power systems for critical loads.'],
                    ['number' => '03', 'image' => '/images/home/generator-maintenance-card.svg', 'title_ar' => 'Generators · المولدات', 'title_en' => 'Generators', 'desc_ar' => 'صيانة وفحص وتشغيل المولدات وأنظمة القدرة الاحتياطية ودعم استمرارية الطاقة.', 'desc_en' => 'Generator maintenance, testing and operation for reliable standby power continuity.'],
                    ['number' => '04', 'image' => '/images/home/facility-power.svg', 'title_ar' => 'MV Systems · أنظمة الجهد المتوسط', 'title_en' => 'MV Systems', 'desc_ar' => 'خدمات أنظمة الجهد المتوسط واللوحات والحماية والاختبارات والتشغيل.', 'desc_en' => 'Medium-voltage systems, switchgear, protection, testing and commissioning services.'],
                    ['number' => '05', 'image' => '/images/home/service-photo-v14-01.webp', 'title_ar' => 'Preventive Maintenance', 'title_en' => 'Preventive Maintenance', 'desc_ar' => 'برامج صيانة وقائية دورية تقلل الأعطال وتحسن جاهزية الأصول والمعدات.', 'desc_en' => 'Planned preventive maintenance programs that reduce failures and improve asset readiness.'],
                    ['number' => '06', 'image' => '/images/home/about-technician-v14.webp', 'title_ar' => 'Corrective / Emergency Maintenance', 'title_en' => 'Corrective / Emergency Maintenance', 'desc_ar' => 'استجابة للأعطال والصيانة التصحيحية والطارئة لإعادة التشغيل بأسرع وقت ممكن.', 'desc_en' => 'Responsive corrective and emergency maintenance to restore operation quickly and safely.'],
                    ['number' => '07', 'image' => '/images/home/service-photo-v14-02.webp', 'title_ar' => 'Inspection & Testing', 'title_en' => 'Inspection & Testing', 'desc_ar' => 'فحص واختبار الأنظمة والمعدات الكهربائية وإصدار تقارير فنية واضحة.', 'desc_en' => 'Electrical equipment and system inspection, testing and clear technical reporting.'],
                    ['number' => '08', 'image' => '/images/home/industry-photo-v14-01.webp', 'title_ar' => 'Maintenance Contracts', 'title_en' => 'Maintenance Contracts', 'desc_ar' => 'عقود صيانة دورية بمستويات خدمة وجداول واضحة ومتابعة وتقارير مستمرة.', 'desc_en' => 'Structured maintenance contracts with service levels, schedules, tracking and reporting.'],
                    ['number' => '09', 'image' => '/images/home/industry-photo-v14-04.webp', 'title_ar' => 'Industrial / Commercial Electrical Services', 'title_en' => 'Industrial / Commercial Electrical Services', 'desc_ar' => 'خدمات كهربائية متخصصة للمنشآت الصناعية والتجارية والمواقع التشغيلية.', 'desc_en' => 'Specialist electrical services for industrial plants, commercial buildings and operating sites.'],
                    ['number' => '10', 'image' => '/images/home/service-photo-v14-05.webp', 'title_ar' => 'Asset Management', 'title_en' => 'Asset Management', 'desc_ar' => 'إدارة دورة حياة الأصول وسجلات الصيانة والضمانات والتاريخ الفني.', 'desc_en' => 'Complete asset lifecycle, maintenance history, warranty and technical record management.'],
                    ['number' => '11', 'image' => '/images/home/service-photo-v14-03.webp', 'title_ar' => 'HVAC Systems', 'title_en' => 'HVAC Systems', 'desc_ar' => 'فحص وصيانة وتشغيل أنظمة التكييف والتهوية وتحسين كفاءة الأداء.', 'desc_en' => 'Inspection, maintenance and operation of HVAC systems to improve efficiency and reliability.'],
                    ['number' => '12', 'image' => '/images/home/industry-photo-v14-03.webp', 'title_ar' => 'MEP Services', 'title_en' => 'MEP Services', 'desc_ar' => 'صيانة وتشغيل الأنظمة الميكانيكية والكهربائية والصحية للمباني والمنشآت.', 'desc_en' => 'Mechanical, electrical and plumbing systems maintenance and operations for buildings and facilities.'],
                    ['number' => '13', 'image' => '/images/home/service-photo-v14-00.webp', 'title_ar' => 'Facility Management', 'title_en' => 'Facility Management', 'desc_ar' => 'إدارة وتشغيل المرافق والخدمات اليومية ومتابعة الأصول والمقاولين والأداء.', 'desc_en' => 'Integrated facility operations, daily services, asset oversight and contractor performance management.'],
                ],
            ]],
            40 => ['process', [
                'kicker_ar' => 'كيف نعمل', 'kicker_en' => 'HOW WE WORK',
                'title_ar' => 'طريقة عمل واضحة من البداية إلى النهاية -- من التقييم إلى التحسين',
                'title_en' => 'A clear process from start to finish',
                'items' => [
                    ['number' => '01', 'title_ar' => 'التقييم', 'title_en' => 'Assessment', 'desc_ar' => 'فهم الموقع والأصول والمخاطر ومتطلبات الخدمة.', 'desc_en' => 'Understand the site, assets, risks and service requirements.'],
                    ['number' => '02', 'title_ar' => 'التخطيط', 'title_en' => 'Planning', 'desc_ar' => 'إعداد خطة العمل والجداول والموارد ومستويات الخدمة.', 'desc_en' => 'Build the work plan, schedule, resources and service levels.'],
                    ['number' => '03', 'title_ar' => 'التنفيذ', 'title_en' => 'Execution', 'desc_ar' => 'تنفيذ الأعمال وتسجيل القراءات والنتائج والملاحظات.', 'desc_en' => 'Deliver the work and document readings, results and findings.'],
                    ['number' => '04', 'title_ar' => 'المتابعة والتحسين', 'title_en' => 'Follow-up & Improvement', 'desc_ar' => 'تقارير ومؤشرات وتحسين مستمر للأداء التشغيلي.', 'desc_en' => 'Report performance and continuously improve operations.'],
                ],
            ]],
            50 => ['industries', [
                'title_ar' => 'القطاعات التي نخدمها', 'title_en' => 'Industries we serve',
                'button_ar' => 'عرض جميع القطاعات', 'button_en' => 'View All Industries',
                'items' => [
                    ['image' => '/images/home/industry-photo-v14-00.webp', 'label_ar' => 'المباني التجارية', 'label_en' => 'Commercial Buildings'],
                    ['image' => '/images/home/industry-photo-v14-01.webp', 'label_ar' => 'المكاتب', 'label_en' => 'Offices'],
                    ['image' => '/images/home/industry-photo-v14-02.webp', 'label_ar' => 'المرافق الصحية', 'label_en' => 'Healthcare Facilities'],
                    ['image' => '/images/home/industry-photo-v14-03.webp', 'label_ar' => 'المستودعات والخدمات اللوجستية', 'label_en' => 'Warehouses & Logistics'],
                    ['image' => '/images/home/industry-photo-v14-04.webp', 'label_ar' => 'القطاع الصناعي', 'label_en' => 'Industrial Facilities'],
                    ['image' => '/images/home/industry-photo-v14-05.webp', 'label_ar' => 'مرافق الضيافة', 'label_en' => 'Hospitality'],
                    ['image' => '/images/home/industry-photo-v14-06.webp', 'label_ar' => 'الجهات الحكومية', 'label_en' => 'Government'],
                ],
            ]],
            60 => ['operations', [
                'maintenance_title_ar' => 'من الصيانة التفاعلية إلى التشغيل المخطط',
                'maintenance_title_en' => 'From reactive maintenance to planned operations',
                'maintenance_text_ar' => 'يقلل النظام الوقائي استجابة UNIFCO للأعطال عبر التخطيط للصيانة قبل حدوث المشكلة.',
                'maintenance_text_en' => 'UNIFCO reduces emergency failures by planning, documenting and improving maintenance before problems interrupt your operation.',
                'maintenance_checks_ar' => ['خطط صيانة وقائية', 'جداول فحص دورية', 'تواريخ وقطع غيار', 'برامج رفع الكفاءة', 'توثيق أوامر العمل', 'تحسين الأداء المستمر'],
                'maintenance_checks_en' => ['Preventive plans', 'Routine inspections', 'History and spare parts', 'Efficiency programs', 'Work order documentation', 'Continuous improvement'],
                'maintenance_button_ar' => 'تعرّف على خدمات الصيانة', 'maintenance_button_en' => 'Explore Maintenance Services',
                'portal_title_ar' => 'إدارة خدماتك من مكان واحد', 'portal_title_en' => 'Manage every service in one place',
                'portal_text_ar' => 'منصة رقمية متكاملة تتيح لك متابعة جميع خدمات منشأتك بسهولة.',
                'portal_text_en' => 'A connected client portal that makes every facility service easy to monitor.',
                'portal_checks_ar' => ['متابعة العقود', 'إدارة الأصول', 'أوامر الصيانة', 'التقارير والمدفوعات', 'الصيانة القادمة', 'مؤشرات الأداء (KPI)'],
                'portal_checks_en' => ['Contract tracking', 'Asset management', 'Maintenance requests', 'Reports and payments', 'Upcoming maintenance', 'SLA performance'],
                'portal_button_ar' => 'دخول حساب العميل', 'portal_button_en' => 'Client Portal Login',
            ]],
            70 => ['why', [
                'title_ar' => 'لماذا UNIFCO؟', 'title_en' => 'Why UNIFCO?',
                'items' => [
                    ['icon' => 'layers', 'title_ar' => 'حلول متكاملة', 'title_en' => 'Integrated Solutions', 'desc_ar' => 'كل خدماتك وأصولك في منصة واحدة', 'desc_en' => 'All services and assets in one platform'],
                    ['icon' => 'report', 'title_ar' => 'تقارير شفافة ودقيقة', 'title_en' => 'Clear Reporting', 'desc_ar' => 'تقارير مفصلة لتعزيز الثقة والوضوح', 'desc_en' => 'Accurate reports that build visibility and trust'],
                    ['icon' => 'clock', 'title_ar' => 'استجابة سريعة', 'title_en' => 'Fast Response', 'desc_ar' => 'إدارة فعالة للأعطال والطوارئ', 'desc_en' => 'Structured handling for failures and emergencies'],
                    ['icon' => 'team', 'title_ar' => 'فريق مؤهل ومتخصص', 'title_en' => 'Qualified Teams', 'desc_ar' => 'مهندسون وفنيون بخبرة ومؤهلات عالية', 'desc_en' => 'Experienced engineers and skilled technicians'],
                    ['icon' => 'settings', 'title_ar' => 'نهج وقائي ذكي', 'title_en' => 'Smart Prevention', 'desc_ar' => 'تركيز على منع الأعطال بخطط مدروسة', 'desc_en' => 'Maintenance plans focused on avoiding failures'],
                    ['icon' => 'target', 'title_ar' => 'تجربة موحدة', 'title_en' => 'One Experience', 'desc_ar' => 'حلول متكاملة من جهة واحدة', 'desc_en' => 'A complete solution from one accountable partner'],
                ],
            ]],
            80 => ['showcase', [
                'kicker_ar' => 'مشاريعنا', 'kicker_en' => 'OUR PROJECTS',
                'title_ar' => 'خبرة موثوقة في تنفيذ المشاريع',
                'title_en' => 'Proven experience in project delivery',
                'text_ar' => 'نفذنا مجموعة واسعة من المشاريع والتعميدات في قطاعات متعددة ومناطق مختلفة بالمملكة بجودة وكفاءة.',
                'text_en' => 'A broad portfolio of maintenance, supply and installation assignments delivered across sectors and regions of Saudi Arabia.',
                'metrics' => [
                    ['icon' => 'pin', 'value_ar' => 'مناطق متعددة', 'value_en' => 'Multiple regions', 'label_ar' => 'في المملكة', 'label_en' => 'Across Saudi Arabia'],
                    ['icon' => 'calendar', 'value_ar' => '2024 - 2026', 'value_en' => '2024 - 2026', 'label_ar' => 'خبرة تنفيذ موثقة', 'label_en' => 'Documented delivery'],
                    ['icon' => 'team', 'value_ar' => '24+', 'value_en' => '24+', 'label_ar' => 'جهة مالكة', 'label_en' => 'Client organizations'],
                    ['icon' => 'report', 'value_ar' => '44+', 'value_en' => '44+', 'label_ar' => 'مشروعًا وتعميدًا', 'label_en' => 'Projects and assignments'],
                ],
                'previous_ar' => 'المشاريع السابقة', 'previous_en' => 'Previous items',
                'next_ar' => 'المشاريع التالية', 'next_en' => 'Next items',
            ]],
            90 => ['clients', [
                'title_ar' => 'عملاؤنا وشركاء النجاح',
                'title_en' => 'Our Clients and Success Partners',
                'text_ar' => 'نفخر بشراكاتنا مع جهات حكومية وخاصة رائدة في مختلف القطاعات.',
                'text_en' => 'We are proud to support leading government and private-sector organizations across multiple industries.',
                'more_ar' => 'المزيد من الجهات', 'more_en' => 'More organizations',
                'button_ar' => 'عرض جميع العملاء', 'button_en' => 'View All Clients',
            ]],
            100 => ['emergency', [
                'title_ar' => 'تحتاج إلى تدخل عاجل؟',
                'title_en' => 'Need urgent support?',
                'text_ar' => 'إذا كان لديك عطل يؤثر على التشغيل، أرسل طلب صيانة طارئة وسيتم التعامل معه مباشرة.',
                'text_en' => 'If a failure is affecting operations, send an emergency maintenance request for immediate routing.',
                'button_ar' => 'طلب صيانة طارئة', 'button_en' => 'Emergency Maintenance',
                'contact_ar' => 'دعم العمليات 24/7 · أرسل طلبك العاجل مباشرة',
                'contact_en' => '24/7 operations support · Send your urgent request directly',
                'photo_alt_ar' => 'فريق UNIFCO في أحد مواقع العمل',
                'photo_alt_en' => 'UNIFCO field team at an operating site',
                'support_ar' => 'دعم العمليات', 'support_en' => 'Operations support',
                'call_ar' => 'اتصل بنا الآن', 'call_en' => 'Call us now',
                'email_ar' => 'راسلنا', 'email_en' => 'Email us',
            ]],
            110 => ['footer_cta', [
                'title_ar' => 'منشأتك تستحق إدارة أفضل',
                'title_en' => 'Your facility deserves better management',
                'text_ar' => 'دع UNIFCO تتولى التشغيل والصيانة، بينما تركز أنت على أعمالك.',
                'text_en' => 'Let UNIFCO manage operations and maintenance while you focus on your business.',
                'quote_ar' => 'اطلب عرض سعر', 'quote_en' => 'Request a Quote',
                'contact_ar' => 'تحدث معنا', 'contact_en' => 'Talk to Us',
            ]],
            120 => ['footer', [
                'about_ar' => 'حلول الأنظمة الكهربائية والطاقة والصيانة وإدارة المرافق والأصول عبر تجربة خدمة واحدة متكاملة.',
                'about_en' => 'Integrated electrical power, maintenance, facility and asset management through one connected service experience.',
                'company_ar' => 'الشركة', 'company_en' => 'Company',
                'services_label_ar' => 'الخدمات', 'services_label_en' => 'Services',
                'contact_label_ar' => 'تواصل معنا', 'contact_label_en' => 'Contact',
                'contact_lines_ar' => ['دعم العمليات على مدار الساعة', 'طلبات الخدمة والاستجابة العاجلة', 'المملكة العربية السعودية'],
                'contact_lines_en' => ['24/7 operations support', 'Service requests and urgent response', 'Saudi Arabia'],
            ]],
        ];

        foreach ($sections as $order => [$key, $data]) {
            HomepageSection::updateOrCreate(
                ['section_key' => $key],
                [
                    'sort_order' => $order,
                    'is_active' => true,
                    'data_ar' => $this->buildSectionData($data, 'ar'),
                    'data_en' => $this->buildSectionData($data, 'en'),
                ]
            );
        }
    }

    private function buildSectionData(array $data, string $locale): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                $result[$key] = array_map(fn (array $item) => $this->buildItemData($item, $locale), $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function buildItemData(array $item, string $locale): array
    {
        $result = [];
        foreach ($item as $k => $v) {
            if (str_ends_with($k, "_{$locale}") || $k === 'icon' || $k === 'image' || $k === 'number' || is_numeric($v)) {
                $baseKey = preg_replace("/_{$locale}$/", '', $k);
                if (str_ends_with($k, "_{$locale}")) {
                    $result[$baseKey] = $v;
                } else {
                    $result[$k] = $v;
                }
            }
        }
        return $result;
    }

    private function seedProjects(): void
    {
        $projects = [
            ['sort' => 0, 'year' => '2024-2025', 'image' => '/images/home/projects/ats-maintenance.webp',
             'title_ar' => 'صيانة المفاتيح الكهربائية ATS', 'title_en' => 'ATS switch maintenance',
             'owner_ar' => 'شركة المياه الوطنية', 'owner_en' => 'National Water Company',
             'location_ar' => 'المدينة المنورة', 'location_en' => 'Madinah',
             'scope_ar' => 'صيانة وقائية وتصحيحية', 'scope_en' => 'Preventive and corrective maintenance'],
            ['sort' => 1, 'year' => '2025-2026', 'image' => '/images/home/projects/generator-maintenance.webp',
             'title_ar' => 'صيانة مولد مبنى المستشفى', 'title_en' => 'Hospital generator maintenance',
             'owner_ar' => 'مستشفى المدينة الوطني', 'owner_en' => 'Madinah National Hospital',
             'location_ar' => 'المدينة المنورة', 'location_en' => 'Madinah',
             'scope_ar' => 'صيانة وقائية وتصحيحية', 'scope_en' => 'Preventive and corrective maintenance'],
            ['sort' => 2, 'year' => '2024-2025', 'image' => '/images/home/projects/transformer-inspection.webp',
             'title_ar' => 'صيانة المحولات ولوحات التوزيع', 'title_en' => 'Transformer and distribution panel maintenance',
             'owner_ar' => 'وزارة الموارد البشرية', 'owner_en' => 'Ministry of Human Resources',
             'location_ar' => 'تبوك', 'location_en' => 'Tabuk',
             'scope_ar' => 'صيانة وقائية وتصحيحية', 'scope_en' => 'Preventive and corrective maintenance'],
            ['sort' => 3, 'year' => '2026', 'image' => '/images/home/projects/transformer-field.webp',
             'title_ar' => 'توريد وتركيب محولات', 'title_en' => 'Transformer supply and installation',
             'owner_ar' => 'مستشفى القطيف المركزي', 'owner_en' => 'Qatif Central Hospital',
             'location_ar' => 'القطيف', 'location_en' => 'Qatif',
             'scope_ar' => 'توريد وتركيب واختبار', 'scope_en' => 'Supply, installation and testing'],
            ['sort' => 4, 'year' => '2025', 'image' => '/images/home/projects/transformer-oil-service.webp',
             'title_ar' => 'فلترة زيت المحولات', 'title_en' => 'Transformer oil filtration',
             'owner_ar' => 'مركز المعلومات STC', 'owner_en' => 'STC Information Center',
             'location_ar' => 'مكة المكرمة', 'location_en' => 'Makkah',
             'scope_ar' => 'إصلاح وتركيب قطع غيار', 'scope_en' => 'Repair and spare-parts installation'],
            ['sort' => 5, 'year' => '2024', 'image' => '/images/home/projects/hvac-maintenance.webp',
             'title_ar' => 'أعمال صيانة للتشيلرات', 'title_en' => 'Chiller maintenance works',
             'owner_ar' => 'سفارة دولة الإمارات العربية المتحدة', 'owner_en' => 'UAE Embassy',
             'location_ar' => 'الرياض', 'location_en' => 'Riyadh',
             'scope_ar' => 'صيانة وقائية وقطع غيار', 'scope_en' => 'Preventive maintenance and spare parts'],
            ['sort' => 6, 'year' => '2024-2026', 'image' => '/images/home/projects/emergency-team.webp',
             'title_ar' => 'صيانة المولدات', 'title_en' => 'Generator maintenance',
             'owner_ar' => 'شركة المياه الوطنية', 'owner_en' => 'National Water Company',
             'location_ar' => 'القصيم', 'location_en' => 'Qassim',
             'scope_ar' => 'صيانة وقائية وتصحيحية', 'scope_en' => 'Preventive and corrective maintenance'],
            ['sort' => 7, 'year' => '2024-2025', 'image' => '/images/home/projects/electrical-testing.webp',
             'title_ar' => 'صيانة المولدات الاحتياطية', 'title_en' => 'Standby generator maintenance',
             'owner_ar' => 'شركة المياه الوطنية', 'owner_en' => 'National Water Company',
             'location_ar' => 'تبوك', 'location_en' => 'Tabuk',
             'scope_ar' => 'فحص وصيانة وتشغيل', 'scope_en' => 'Inspection, maintenance and operation'],
            ['sort' => 8, 'year' => '2025', 'image' => '/images/home/projects/site-response.webp',
             'title_ar' => 'صيانة مولد قوة الأمن الخاصة', 'title_en' => 'Special Security Force generator maintenance',
             'owner_ar' => 'قوة الأمن الخاصة الرابعة', 'owner_en' => 'Fourth Special Security Force',
             'location_ar' => 'المدينة المنورة', 'location_en' => 'Madinah',
             'scope_ar' => 'إصلاح وتركيب قطع غيار', 'scope_en' => 'Repair and spare-parts installation'],
            ['sort' => 9, 'year' => '2025', 'image' => '/images/home/projects/chiller-service.webp',
             'title_ar' => 'إنشاء غرف تبريد', 'title_en' => 'Cold-room construction',
             'owner_ar' => 'شركة المراعي', 'owner_en' => 'Almarai',
             'location_ar' => 'حفر الباطن', 'location_en' => 'Hafar Al Batin',
             'scope_ar' => 'توريد وتركيب واختبار', 'scope_en' => 'Supply, installation and testing'],
        ];

        foreach ($projects as $p) {
            HomepageProject::create([
                'sort_order' => $p['sort'],
                'is_active' => true,
                'year' => $p['year'],
                'image' => $p['image'],
                'title_ar' => $p['title_ar'],
                'title_en' => $p['title_en'],
                'owner_ar' => $p['owner_ar'],
                'owner_en' => $p['owner_en'],
                'location_ar' => $p['location_ar'],
                'location_en' => $p['location_en'],
                'scope_ar' => $p['scope_ar'],
                'scope_en' => $p['scope_en'],
            ]);
        }
    }

    private function seedClients(): void
    {
        $clients = [
            ['sort' => 0, 'image' => '/images/home/clients/nwc.webp', 'name_ar' => 'شركة المياه الوطنية', 'name_en' => 'National Water Company'],
            ['sort' => 1, 'image' => '/images/home/clients/hrsd.webp', 'name_ar' => 'وزارة الموارد البشرية والتنمية الاجتماعية', 'name_en' => 'Ministry of Human Resources and Social Development'],
            ['sort' => 2, 'image' => '/images/home/clients/islamic-university.webp', 'name_ar' => 'الجامعة الإسلامية بالمدينة المنورة', 'name_en' => 'Islamic University of Madinah'],
            ['sort' => 3, 'image' => '/images/home/clients/ministry-health.webp', 'name_ar' => 'وزارة الصحة', 'name_en' => 'Ministry of Health'],
            ['sort' => 4, 'image' => '/images/home/clients/uae-embassy.webp', 'name_ar' => 'سفارة دولة الإمارات العربية المتحدة', 'name_en' => 'Embassy of the United Arab Emirates'],
            ['sort' => 5, 'image' => '/images/home/clients/stc.webp', 'name_ar' => 'STC', 'name_en' => 'STC'],
            ['sort' => 6, 'image' => '/images/home/clients/almarai.webp', 'name_ar' => 'شركة المراعي', 'name_en' => 'Almarai'],
            ['sort' => 7, 'image' => '/images/home/clients/sdb.webp', 'name_ar' => 'بنك التنمية الاجتماعية', 'name_en' => 'Social Development Bank'],
        ];

        foreach ($clients as $c) {
            HomepageClient::create([
                'sort_order' => $c['sort'],
                'is_active' => true,
                'image' => $c['image'],
                'name_ar' => $c['name_ar'],
                'name_en' => $c['name_en'],
            ]);
        }
    }
}
