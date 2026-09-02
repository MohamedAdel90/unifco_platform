@php
$home = [
    'lang' => 'en',
    'dir' => 'ltr',
    'nav' => ['Home', 'About', 'Services', 'Industries', 'How We Work', 'Projects', 'Contact'],
    'language' => 'AR',
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
    'about_points' => [['target','Central Management','For assets and maintenance'],['team','One Team','For multiple services'],['report','Routine Tracking','With transparent reports'],['layers','Tailored Solutions','For every facility']],
    'about_button' => 'Discover UNIFCO',
    'stats' => [['+','Sites Served'], ['+','Assets Managed'], ['98%','SLA Compliance'], ['24/7','Operations Support']],
    'services_kicker' => '',
    'services_title' => 'Our Services',
    'services_text' => 'From critical power systems and specialist maintenance to asset and facility management',
    'services' => [
        ['01','/images/home/service-photo-v14-04.webp','Transformers','Inspection, maintenance and operation of electrical transformers to improve reliability and performance.'],
        ['02','/images/home/service-photo-v14-04.webp','UPS Systems','Supply, inspection and maintenance of UPS and uninterrupted power systems for critical loads.'],
        ['03','/images/home/service-photo-v14-02.webp','Generators','Generator maintenance, testing and operation for reliable standby power continuity.'],
        ['04','/images/home/service-photo-v14-04.webp','MV Systems','Medium-voltage systems, switchgear, protection, testing and commissioning services.'],
        ['05','/images/home/service-photo-v14-01.webp','Preventive Maintenance','Planned preventive maintenance programs that reduce failures and improve asset readiness.'],
        ['06','/images/home/service-photo-v14-01.webp','Corrective / Emergency Maintenance','Responsive corrective and emergency maintenance to restore operation quickly and safely.'],
        ['07','/images/home/service-photo-v14-02.webp','Inspection & Testing','Electrical equipment and system inspection, testing and clear technical reporting.'],
        ['08','/images/home/service-photo-v14-00.webp','Maintenance Contracts','Structured maintenance contracts with service levels, schedules, tracking and reporting.'],
        ['09','/images/home/service-photo-v14-04.webp','Industrial / Commercial Electrical Services','Specialist electrical services for industrial plants, commercial buildings and operating sites.'],
        ['10','/images/home/service-photo-v14-05.webp','Asset Management','Complete asset lifecycle, maintenance history, warranty and technical record management.'],
        ['11','/images/home/service-photo-v14-03.webp','HVAC Systems','Inspection, maintenance and operation of HVAC systems to improve efficiency and reliability.'],
        ['12','/images/home/service-photo-v14-02.webp','MEP Services','Mechanical, electrical and plumbing systems maintenance and operations for buildings and facilities.'],
        ['13','/images/home/service-photo-v14-00.webp','Facility Management','Integrated facility operations, daily services, asset oversight and contractor performance management.'],
    ],
    'more' => 'Learn more','all_services' => 'View All Services','process_kicker' => 'HOW WE WORK','process_title' => 'A clear process from start to finish',
    'process' => [['01','Assessment','Understand the site, assets, risks and service requirements.'],['02','Planning','Build the work plan, schedule, resources and service levels.'],['03','Execution','Deliver the work and document readings, results and findings.'],['04','Follow-up & Improvement','Report performance and continuously improve operations.']],
    'industries_title' => 'Industries we serve',
    'industries' => [['/images/home/industry-photo-v14-00.webp','Commercial Buildings'],['/images/home/industry-photo-v14-01.webp','Offices'],['/images/home/industry-photo-v14-02.webp','Healthcare Facilities'],['/images/home/industry-photo-v14-03.webp','Warehouses & Logistics'],['/images/home/industry-photo-v14-04.webp','Industrial Facilities'],['/images/home/industry-photo-v14-05.webp','Hospitality'],['/images/home/industry-photo-v14-06.webp','Government']],
    'all_industries' => 'View All Industries','maintenance_title' => 'From reactive maintenance to planned operations','maintenance_text' => 'UNIFCO reduces emergency failures by planning, documenting and improving maintenance before problems interrupt your operation.',
    'maintenance_checks' => ['Preventive plans','Routine inspections','History and spare parts','Efficiency programs','Work order documentation','Continuous improvement'],'maintenance_button' => 'Explore Maintenance Services',
    'portal_title' => 'Manage every service in one place','portal_text' => 'A connected client portal that makes every facility service easy to monitor.','portal_checks' => ['Contract tracking','Asset management','Maintenance requests','Reports and payments','Upcoming maintenance','SLA performance'],'portal_button' => 'Client Portal Login',
    'why_title' => 'Why UNIFCO?','why' => [['layers','Integrated Solutions','All services and assets in one platform'],['report','Clear Reporting','Accurate reports that build visibility and trust'],['clock','Fast Response','Structured handling for failures and emergencies'],['team','Qualified Teams','Experienced engineers and skilled technicians'],['settings','Smart Prevention','Maintenance plans focused on avoiding failures'],['target','One Experience','A complete solution from one accountable partner']],
    'projects_title' => 'Our work in action','projects_button' => 'View All Projects','emergency_title' => 'Need urgent support?','emergency_text' => 'If a failure is affecting operations, send an emergency maintenance request for immediate routing.','emergency_button' => 'Emergency Maintenance','emergency_contact' => '24/7 operations support · Send your urgent request directly',
    'final_title' => 'Your facility deserves better management','final_text' => 'Let UNIFCO manage operations and maintenance while you focus on your business.','quote' => 'Request a Quote','contact' => 'Talk to Us','footer_about' => 'Integrated electrical power, maintenance, facility and asset management through one connected service experience.','company' => 'Company','services_label' => 'Services','contact_label' => 'Contact','footer_contact' => ['24/7 operations support','Service requests and urgent response','Saudi Arabia'],
];
@endphp
@include('public.partials.home-reference-layout', ['home' => $home])
<style id="unifco-operation-cards-refresh-en">
.operations{padding:34px 0 64px;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%)}
.operation-split{grid-template-columns:1fr 1fr;gap:18px;overflow:visible;box-shadow:none;border-radius:0;align-items:stretch}
.operation-card{min-height:420px;border-radius:18px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 18px 44px rgba(7,31,77,.10)}
.maintenance-card{grid-template-columns:34% 66%;background:linear-gradient(145deg,#08275a 0%,#0b356d 100%);border-color:#153e73}.maintenance-photo{min-height:420px}.maintenance-photo img{height:100%;object-position:center}.operation-content{padding:40px 34px;display:flex;flex-direction:column;justify-content:center}.operation-card h2{font-size:clamp(25px,2.1vw,32px);line-height:1.35;margin-bottom:14px;position:relative;padding-bottom:15px}.operation-card h2:after{content:"";position:absolute;bottom:0;left:0;width:42px;height:4px;border-radius:99px;background:var(--red)}.operation-card p{font-size:12px;line-height:1.95}.check-grid{gap:12px 20px;margin:20px 0 24px}.check{font-size:10.5px;font-weight:800}.check:before{width:18px;height:18px;font-size:9px}
.portal-card{padding:38px 34px;display:grid;grid-template-columns:minmax(0,1fr) 45%;grid-template-areas:"title device" "copy device" "checks device" "button device";grid-template-rows:auto auto auto 1fr;column-gap:28px;align-content:center;direction:ltr;background:linear-gradient(145deg,#fff 0%,#f7f9fc 100%)}.portal-card h2{grid-area:title;align-self:end}.portal-card>p{grid-area:copy}.portal-card .check-grid{grid-area:checks}.portal-card>.btn{grid-area:button;justify-self:start;align-self:start;min-width:160px}.portal-device{grid-area:device;width:100%;height:320px;max-height:none;margin:0;align-self:center;justify-self:end;object-fit:contain;object-position:center;filter:drop-shadow(0 22px 24px rgba(9,29,58,.18))}
@media(max-width:1080px){.operation-split{grid-template-columns:1fr}.operation-card{min-height:390px}.maintenance-photo{min-height:390px}.portal-device{height:280px}}@media(max-width:700px){.operations{padding:24px 0 44px}.operation-card{border-radius:14px}.maintenance-card{grid-template-columns:1fr}.maintenance-photo{min-height:250px;max-height:250px}.operation-content{padding:28px 22px}.portal-card{padding:28px 22px;grid-template-columns:1fr;grid-template-areas:"title" "copy" "checks" "device" "button";row-gap:8px}.portal-device{width:100%;height:230px;justify-self:center;margin:4px 0 8px}.portal-card>.btn{justify-self:stretch}.check-grid{grid-template-columns:1fr 1fr;gap:10px}}
</style>
