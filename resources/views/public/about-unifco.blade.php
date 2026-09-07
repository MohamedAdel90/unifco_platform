@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $homeUrl = route('public.home', ['lang' => $isAr ? 'ar' : 'en']);
    $switchUrl = route('public.about', ['lang' => $isAr ? 'en' : 'ar']);
    $aboutSection = \App\Models\HomepageSection::getByKey('about');
    $pageData = $aboutSection ? (($isAr ? $aboutSection->data_ar : $aboutSection->data_en) ?? []) : [];
    $value = function (string $key, string $fallback = '') use ($pageData) {
        $stored = trim((string) ($pageData[$key] ?? ''));
        return $stored !== '' ? $stored : $fallback;
    };

    $defaults = $isAr ? [
        'strapline' => 'مرافق أكثر... مستقبل أفضل',
        'eyebrow' => 'UNIFCO',
        'title' => 'من نحن',
        'subtitle' => 'وحدة المرافق للمقاولات',
        'intro' => 'وحدة المرافق للمقاولات منشأة سعودية 100% مصنفة في مجالات التشغيل والصيانة والإنشاءات. لدينا اعتمادات وعقود متعددة مع جهات حكومية وخاصة كمقاول ومزود خدمة، ونعمل لتحقيق أعلى معايير الجودة من خلال فرق فنية مؤهلة وقدرات تقنية قوية وأدوات مناسبة وإدارة محترفة وأنظمة تقنية داعمة.',
        'intro2' => 'في وحدة المرافق للمقاولات لم نأت لتكرار ما هو قائم؛ بل لإعادة تعريف إدارة المرافق والتشغيل بمنهج يرتكز على الدقة والابتكار والالتزام الصارم بالجودة. نصنع قيمة مستدامة تمكّن عملاءنا من التركيز على رؤيتهم بينما نتولى التفاصيل التشغيلية بدقة واحتراف.',
        'quote' => 'نؤمن بأن المرافق ليست مجرد مبانٍ أو أنظمة، بل بيئات حيوية تستحق إدارة ذكية تحافظ على الكفاءة وتعزز الأداء على المدى الطويل.',
        'apart_title' => 'ما يميزنا',
        'apart_text' => 'نحن لا ننفذ العمل فقط، بل نتقنه. ما يميزنا هو منهجية التنفيذ، وفرقنا المتخصصة، ومرونتنا في بناء حلول تناسب احتياج كل عميل.',
        'mission_title' => 'رسالتنا',
        'mission_text' => 'بناء بيئة تشغيلية ذكية وآمنة ومتطورة تمكّن عملاءنا من التركيز على أعمالهم الأساسية بينما نتولى التفاصيل الفنية والتشغيلية بدقة واحتراف.',
        'vision_title' => 'رؤيتنا',
        'vision_text' => 'أن نصبح الشركة السعودية الرائدة إقليميًا في تقديم الحلول المتكاملة لإدارة المرافق والتشغيل والصيانة، كشريك استراتيجي للمستقبل.',
        'people_title' => 'فريقنا وتقنياتنا',
        'people_text' => 'فريقنا هو رأس مالنا الحقيقي، ويضم مهندسين وفنيين ومتخصصين مدربين وفق معايير عالية، وتدعمهم تقنيات ترفع الأداء والشفافية وجودة القرار.',
        'footer_note' => 'UNIFCO — تشغيل وصيانة وإدارة مرافق بمنهج احترافي يرتكز على الجودة والكفاءة والابتكار والاستدامة.',
    ] : [
        'strapline' => 'More Facilities... A Better Future',
        'eyebrow' => 'UNIFCO',
        'title' => 'About Us',
        'subtitle' => 'UNIFCO Facilities Contracting',
        'intro' => 'UNIFCO Facilities Contracting is a 100% Saudi enterprise, classified in the fields of operation, maintenance, and construction. We hold approvals and multiple contracts with government and private entities as a subcontractor and service provider. We work to achieve the highest quality standards through highly qualified teams, strong technical capabilities, the right tools, professional management, and supportive technology systems.',
        'intro2' => 'At UNIFCO Facilities Contracting, we did not come to repeat what already exists. We came to redefine facility management and operations through a methodology built on precision, innovation, and an uncompromising commitment to quality. We create sustainable value that enables our clients to focus on their vision while we handle operational details with accuracy and professionalism.',
        'quote' => 'We believe facilities are not merely structures or systems, but vital environments that deserve intelligent management to preserve efficiency and strengthen long-term performance.',
        'apart_title' => 'What Sets Us Apart',
        'apart_text' => 'We do not simply do the work; we master it. What distinguishes us is our philosophy of execution, specialized teams, and flexible solutions designed around each client.',
        'mission_title' => 'Our Mission',
        'mission_text' => 'Our mission is to build a smart, safe, and advanced operating environment that enables our clients to focus on their core business while we manage technical and operational details with precision.',
        'vision_title' => 'Our Vision',
        'vision_text' => 'To become the leading Saudi company regionally in delivering integrated solutions for facility management, operations, and maintenance as a strategic partner for the future.',
        'people_title' => 'Our People & Technology',
        'people_text' => 'Our people are our strongest asset. Our engineers, technicians and specialists are supported by technologies that improve performance, transparency and decision-making.',
        'footer_note' => 'UNIFCO — Facility management, operations and maintenance built around quality, efficiency, innovation and sustainability.',
    ];

    $defaultValues = $isAr ? [
        ['icon' => '▦', 'title' => 'إدارة المرافق', 'subtitle' => 'عمليات عالية الكفاءة'],
        ['icon' => '◎', 'title' => 'الجودة', 'subtitle' => 'معايير عالمية'],
        ['icon' => '⚒', 'title' => 'الهندسة', 'subtitle' => 'خبرات هندسية متكاملة'],
        ['icon' => '◇', 'title' => 'استراتيجي', 'subtitle' => 'شراكة طويلة المدى'],
        ['icon' => '▤', 'title' => 'الابتكار', 'subtitle' => 'حلول تصنع الفارق'],
        ['icon' => '▥', 'title' => 'الاستدامة', 'subtitle' => 'نحو مستقبل مسؤول'],
        ['icon' => '♢', 'title' => 'السلامة', 'subtitle' => 'بيئة عمل أكثر أمانًا'],
    ] : [
        ['icon' => '▦', 'title' => 'Facility Management', 'subtitle' => 'High-efficiency operations'],
        ['icon' => '◎', 'title' => 'Quality', 'subtitle' => 'Global standards'],
        ['icon' => '⚒', 'title' => 'Engineering', 'subtitle' => 'Integrated engineering expertise'],
        ['icon' => '◇', 'title' => 'Strategic', 'subtitle' => 'Long-term partnership'],
        ['icon' => '▤', 'title' => 'Innovation', 'subtitle' => 'Solutions that make the difference'],
        ['icon' => '▥', 'title' => 'Sustainability', 'subtitle' => 'Towards a responsible future'],
        ['icon' => '♢', 'title' => 'Safety', 'subtitle' => 'A safer work environment'],
    ];
    $pageValues = is_array($pageData['page_values'] ?? null) && count($pageData['page_values']) ? $pageData['page_values'] : $defaultValues;
@endphp
<!doctype html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>{{ $value('page_title', $defaults['title']) }} | UNIFCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--navy:#071f4d;--navy2:#103d73;--red:#ce122d;--text:#20324e;--muted:#65748a;--line:#e1e7ef;--light:#f5f8fb}
*{box-sizing:border-box}body{margin:0;background:#fff;color:var(--text);font-family:{{ $isAr ? "'Cairo',Tahoma,Arial,sans-serif" : "'Inter',Arial,sans-serif" }}}a{text-decoration:none;color:inherit}.wrap{width:min(1420px,94%);margin:auto}
.top{position:sticky;top:0;z-index:20;background:#fff;border-bottom:1px solid #e7ebf0}.topin{min-height:74px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{display:flex;align-items:center;gap:11px}.brand img{width:58px;height:58px;object-fit:contain}.brand strong{display:block;font-family:Inter,Arial,sans-serif;color:var(--navy);font-size:25px;letter-spacing:.04em}.brand small{display:block;color:var(--red);font-size:7px;font-weight:900;letter-spacing:.15em;margin-top:3px}.actions{display:flex;gap:9px;align-items:center}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 16px;border-radius:7px;font-size:11px;font-weight:900;border:1px solid #d5dde7;background:#fff;color:var(--navy)}.btn.red{background:var(--red);color:#fff;border-color:var(--red)}
.content{padding:40px 0}.about-shell{border:1px solid var(--line);border-radius:24px;padding:30px;background:#fff;box-shadow:0 10px 38px rgba(7,31,77,.05)}.topline{display:flex;align-items:center;gap:18px;margin-bottom:24px;color:var(--navy);font-size:12px;font-weight:800}.topline:before,.topline:after{content:"";height:1px;background:var(--line);flex:1}.intro{display:grid;grid-template-columns:.82fr 1.18fr;gap:28px;align-items:stretch}.visual{border-radius:18px;overflow:hidden;background:linear-gradient(145deg,#0a2b55,#174d7c);min-height:480px}.visual img{display:block;width:100%;height:100%;min-height:480px;object-fit:cover}.copy{padding:8px 0}.eyebrow{color:var(--red);font-weight:900;letter-spacing:.1em;font-size:14px}.copy h1{margin:6px 0 2px;color:var(--navy);font-size:clamp(42px,5.3vw,76px);line-height:1.05}.copy h2{margin:0 0 20px;color:var(--navy);font-size:22px}.copy p{font-size:14px;line-height:1.95;color:#405574;margin:0 0 13px}.quote{margin-top:16px;padding:20px 24px;background:#f4f7fa;border-radius:11px;border:1px solid #e7ebf0;color:#425775;font-size:13px;line-height:1.9;position:relative}.quote:after{content:'“';position:absolute;top:-4px;right:18px;color:var(--red);font-size:48px;font-weight:900;line-height:1}.values{display:grid;grid-template-columns:repeat(7,1fr);margin:24px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}.value{padding:18px 10px;text-align:center;position:relative;min-width:0}.value:not(:last-child):after{content:"";position:absolute;top:24%;bottom:24%;right:0;width:1px;background:var(--line)}[dir=rtl] .value:not(:last-child):after{right:auto;left:0}.value-icon{font-size:31px;line-height:1;color:var(--navy);min-height:34px}.value b{display:block;margin-top:8px;color:var(--navy);font-size:12px}.value small{display:block;margin-top:4px;color:#708098;font-size:9px;line-height:1.4}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.card{border:1px solid var(--line);border-radius:14px;padding:22px;background:#fff}.card h3{display:flex;align-items:center;gap:9px;color:var(--navy);font-size:20px;margin:0 0 10px}.card h3:before{content:"";width:5px;height:23px;border-radius:6px;background:var(--red)}.card p{margin:0;color:#56667d;font-size:12.5px;line-height:2}.wide{grid-column:1/-1}.footer-note{margin-top:18px;padding:18px 20px;background:#f2f6fa;border-radius:12px;border-inline-start:4px solid var(--red);color:#17345e;font-size:12px;line-height:1.9}
@media(max-width:1080px){.values{grid-template-columns:repeat(4,1fr)}.value:nth-child(4):after{display:none}.intro{grid-template-columns:.9fr 1.1fr}.visual,.visual img{min-height:410px}}
@media(max-width:820px){.topin{min-height:66px}.brand img{width:48px;height:48px}.brand strong{font-size:20px}.actions .btn{padding:8px 11px}.content{padding:22px 0}.about-shell{padding:18px;border-radius:16px}.intro{grid-template-columns:1fr}.visual,.visual img{min-height:260px;max-height:520px}.copy h1{font-size:44px}.values{grid-template-columns:repeat(2,1fr)}.value:nth-child(4):after{display:block}.value:nth-child(2n):after{display:none}.cards{grid-template-columns:1fr}.wide{grid-column:auto}}
</style>
</head>
<body>
<header class="top"><div class="wrap topin">
<a class="brand" href="{{ $homeUrl }}"><img src="{{ route('brand.logo') }}" alt="UNIFCO"><span><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>
<div class="actions"><a class="btn" href="{{ $switchUrl }}">{{ $isAr ? 'EN' : 'AR' }}</a><a class="btn red" href="{{ $homeUrl }}">{{ $isAr ? 'العودة للرئيسية' : 'Back to Home' }}</a></div>
</div></header>
<main class="content"><div class="wrap"><section class="about-shell">
<div class="topline">{{ $value('page_strapline', $defaults['strapline']) }}</div>
<section class="intro">
<div class="visual"><img src="{{ $value('page_visual_image', '/images/home/about-technician-v14.webp') }}" alt="{{ $value('page_title', $defaults['title']) }}"></div>
<div class="copy">
<div class="eyebrow">{{ $value('page_eyebrow', $defaults['eyebrow']) }}</div>
<h1>{{ $value('page_title', $defaults['title']) }}</h1>
<h2>{{ $value('page_subtitle', $defaults['subtitle']) }}</h2>
<p>{{ $value('page_intro_text', $defaults['intro']) }}</p>
<p>{{ $value('page_intro_secondary_text', $defaults['intro2']) }}</p>
<div class="quote">{{ $value('page_quote_text', $defaults['quote']) }}</div>
</div>
</section>
<section class="values">
@foreach($pageValues as $item)
<div class="value"><div class="value-icon">{{ $item['icon'] ?? '◇' }}</div><b>{{ $item['title'] ?? '' }}</b><small>{{ $item['subtitle'] ?? '' }}</small></div>
@endforeach
</section>
<section class="cards">
<article class="card"><h3>{{ $value('page_apart_title', $defaults['apart_title']) }}</h3><p>{{ $value('page_apart_text', $defaults['apart_text']) }}</p></article>
<article class="card"><h3>{{ $value('page_mission_title', $defaults['mission_title']) }}</h3><p>{{ $value('page_mission_text', $defaults['mission_text']) }}</p></article>
<article class="card"><h3>{{ $value('page_vision_title', $defaults['vision_title']) }}</h3><p>{{ $value('page_vision_text', $defaults['vision_text']) }}</p></article>
<article class="card wide"><h3>{{ $value('page_people_title', $defaults['people_title']) }}</h3><p>{{ $value('page_people_text', $defaults['people_text']) }}</p></article>
</section>
<div class="footer-note">{{ $value('page_footer_note_text', $defaults['footer_note']) }}</div>
</section></div></main>
</body></html>
