@php
    $isAr = ($locale ?? 'ar') === 'ar';
    $dir = $isAr ? 'rtl' : 'ltr';
    $homeUrl = route('public.home', ['lang' => $isAr ? 'ar' : 'en']);
    $switchUrl = route('public.about', ['lang' => $isAr ? 'en' : 'ar']);
@endphp
<!doctype html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>{{ $isAr ? 'تعرف على UNIFCO' : 'About UNIFCO' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--navy:#071f4d;--navy2:#103d73;--red:#ce122d;--text:#20324e;--muted:#65748a;--line:#e1e7ef;--light:#f5f8fb}
*{box-sizing:border-box}body{margin:0;background:#fff;color:var(--text);font-family:{{ $isAr ? "'Cairo',Tahoma,Arial,sans-serif" : "'Inter',Arial,sans-serif" }}}a{text-decoration:none;color:inherit}.wrap{width:min(1180px,92%);margin:auto}
.top{position:sticky;top:0;z-index:20;background:#fff;border-bottom:1px solid #e7ebf0}.topin{min-height:74px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{display:flex;align-items:center;gap:11px}.brand img{width:58px;height:58px;object-fit:contain}.brand strong{display:block;font-family:Inter,Arial,sans-serif;color:var(--navy);font-size:25px;letter-spacing:.04em}.brand small{display:block;color:var(--red);font-size:7px;font-weight:900;letter-spacing:.15em;margin-top:3px}.actions{display:flex;gap:9px;align-items:center}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 16px;border-radius:7px;font-size:11px;font-weight:900;border:1px solid #d5dde7;background:#fff;color:var(--navy)}.btn.red{background:var(--red);color:#fff;border-color:var(--red)}
.hero{background:linear-gradient(120deg,#061d45,#0b3267);color:#fff;padding:66px 0 62px;border-bottom:4px solid var(--red)}.hero .k{font-family:Inter,Arial,sans-serif;font-size:12px;font-weight:900;letter-spacing:.08em;color:#d0dced}.hero h1{font-size:clamp(34px,5vw,58px);line-height:1.2;margin:8px 0 14px}.hero p{max-width:850px;margin:0;font-size:14px;line-height:2;color:#dbe5f2}
.content{padding:46px 0}.intro{display:grid;grid-template-columns:1.05fr .95fr;gap:34px;align-items:stretch;margin-bottom:34px}.copy,.visual{border:1px solid var(--line);border-radius:16px;background:#fff;overflow:hidden}.copy{padding:28px}.copy h2{color:var(--navy);font-size:27px;margin:0 0 12px}.copy p{font-size:13px;line-height:2.1;color:#53637a;margin:0 0 14px}.visual{background:linear-gradient(145deg,#eff4f9,#fff);display:grid;place-items:center;min-height:330px;padding:16px}.visual img{width:100%;height:100%;min-height:300px;object-fit:cover;border-radius:12px}
.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.card{border:1px solid var(--line);border-radius:14px;padding:22px;background:#fbfcfe}.card h3{display:flex;align-items:center;gap:9px;color:var(--navy);font-size:20px;margin:0 0 10px}.card h3:before{content:"";width:5px;height:23px;border-radius:6px;background:var(--red)}.card p{margin:0;color:#56667d;font-size:12.5px;line-height:2}.wide{grid-column:1/-1}.footer-note{margin-top:18px;padding:18px 20px;background:#f2f6fa;border-radius:12px;border-inline-start:4px solid var(--red);color:#17345e;font-size:12px;line-height:1.9}
@media(max-width:820px){.topin{min-height:66px}.brand img{width:48px;height:48px}.brand strong{font-size:20px}.actions .btn{padding:8px 11px}.hero{padding:46px 0}.intro{grid-template-columns:1fr}.visual{min-height:230px}.visual img{min-height:210px}.cards{grid-template-columns:1fr}.wide{grid-column:auto}.copy{padding:22px}.content{padding:30px 0}}
</style>
</head>
<body>
<header class="top"><div class="wrap topin">
<a class="brand" href="{{ $homeUrl }}"><img src="{{ route('brand.logo') }}" alt="UNIFCO"><span><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>
<div class="actions"><a class="btn" href="{{ $switchUrl }}">{{ $isAr ? 'EN' : 'AR' }}</a><a class="btn red" href="{{ $homeUrl }}">{{ $isAr ? 'العودة للرئيسية' : 'Back to Home' }}</a></div>
</div></header>
<section class="hero"><div class="wrap"><div class="k">UNIFCO · ONE FACILITY SHOP</div><h1>{{ $isAr ? 'تعرف على UNIFCO' : 'About UNIFCO' }}</h1><p>{{ $isAr ? 'وحدة المرافق للمقاولات منشأة سعودية متخصصة في إدارة المرافق والتشغيل والصيانة والحلول الفنية المتكاملة، بمنهج يرتكز على الجودة والكفاءة والاستدامة.' : 'UNIFCO is a Saudi facility management, operations and maintenance company delivering integrated technical solutions with a focus on quality, efficiency and sustainability.' }}</p></div></section>
<main class="content"><div class="wrap">
<section class="intro"><div class="copy"><h2>{{ $isAr ? 'من نحن' : 'Who We Are' }}</h2>
@if($isAr)
<p>وحدة المرافق منشأة سعودية 100% مصنفة في مجال التشغيل والصيانة والإنشاءات، ولديها خبرات وعقود مع جهات حكومية وخاصة كمقاول ومزود خدمة. نعمل لتحقيق أعلى متطلبات الجودة من خلال فرق فنية متخصصة وإدارة محترفة وأنظمة تقنية مساعدة.</p>
<p>نحن لا نقدم خدمات تشغيلية تقليدية فقط، بل نبني قيمة مستدامة تمكّن عملاءنا من التركيز على أعمالهم الأساسية، بينما نتولى نحن التفاصيل الفنية والتشغيلية بكفاءة واحتراف.</p>
@else
<p>UNIFCO is a 100% Saudi company active in operations, maintenance and contracting, serving government and private-sector clients through qualified technical teams, professional management and supporting digital systems.</p>
<p>We go beyond traditional maintenance services by creating sustainable operational value, allowing our clients to focus on their core business while we manage technical and facility requirements with discipline and professionalism.</p>
@endif
</div><div class="visual"><img src="/images/home/about-technician-v14.webp" alt="UNIFCO"></div></section>
<section class="cards">
<article class="card"><h3>{{ $isAr ? 'رؤيتنا' : 'Our Vision' }}</h3><p>{{ $isAr ? 'أن نكون شركة سعودية رائدة إقليميًا في تقديم حلول متكاملة لإدارة المرافق والتشغيل والصيانة، كشريك استراتيجي يدعم المدن الذكية والبنية التحتية المستدامة.' : 'To be a leading Saudi regional provider of integrated facility, operations and maintenance solutions, acting as a strategic partner for smarter and more sustainable infrastructure.' }}</p></article>
<article class="card"><h3>{{ $isAr ? 'رسالتنا' : 'Our Mission' }}</h3><p>{{ $isAr ? 'بناء بيئة تشغيلية ذكية وآمنة ومتطورة تمكّن عملاءنا من التركيز على أهدافهم الأساسية، بينما نتولى التفاصيل التقنية والتشغيلية بدقة واحتراف.' : 'To create safe, intelligent and efficient operating environments that let our clients focus on their goals while we manage technical and operational details with precision.' }}</p></article>
<article class="card"><h3>{{ $isAr ? 'ما يميزنا' : 'What Sets Us Apart' }}</h3><p>{{ $isAr ? 'حلول مرنة مصممة وفق احتياج كل عميل، فرق متخصصة، التزام بالتنفيذ، وتركيز مستمر على الجودة والكفاءة والتحسين.' : 'Flexible client-specific solutions, specialized teams, disciplined execution and a continuous focus on quality, efficiency and improvement.' }}</p></article>
<article class="card wide"><h3>{{ $isAr ? 'فريقنا وتقنياتنا' : 'Our People & Technology' }}</h3><p>{{ $isAr ? 'فريقنا هو رأس مالنا الحقيقي، ويضم مهندسين وفنيين ومتخصصين مدربين وفق معايير عالية. ونستخدم التقنية كجزء أساسي من منظومة التشغيل لتحسين الأداء، رفع الشفافية، تقليل التكاليف ودعم القرارات.' : 'Our people are our strongest asset. Our engineers, technicians and specialists are supported by technology that improves performance, transparency, cost control and decision-making.' }}</p></article>
</section>
<div class="footer-note">{{ $isAr ? 'UNIFCO — تشغيل وصيانة وإدارة مرافق بمنهج احترافي يرتكز على الجودة والكفاءة والابتكار والاستدامة.' : 'UNIFCO — Facility management, operations and maintenance built around quality, efficiency, innovation and sustainability.' }}</div>
</div></main>
</body></html>
