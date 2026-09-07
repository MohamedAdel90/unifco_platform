@php
    $headerHome = $home ?? app(\App\Services\HomepageContentService::class)->getContent($locale ?? 'ar');
    $headerLocale = $headerHome['lang'] ?? ($locale ?? 'ar');
    $headerBase = route($headerLocale === 'ar' ? 'public.home' : 'public.home.en');
    $languageTarget = $headerLocale === 'ar' ? 'en' : 'ar';
    $languageUrl = route('public.home', ['lang' => $languageTarget]);
    $headerLabels = $headerLocale === 'ar' ? [
        'home' => 'الرئيسية',
        'about' => 'تعرف علينا',
        'services' => 'الخدمات',
        'industries' => 'القطاعات',
        'projects' => 'المشاريع',
        'clients' => 'العملاء',
        'careers' => 'الوظائف',
        'contact' => 'تواصل معنا',
    ] : [
        'home' => 'Home',
        'about' => 'About Us',
        'services' => 'Services',
        'industries' => 'Industries',
        'projects' => 'Projects',
        'clients' => 'Clients',
        'careers' => 'Careers',
        'contact' => 'Contact Us',
    ];
    $headerItems = [
        ['home', '#home'],
        ['about', '#about'],
        ['services', '#services'],
        ['industries', '#industries'],
        ['projects', '#projects'],
        ['clients', '#clients'],
        ['careers', '#careers'],
        ['contact', '#contact'],
    ];
    $headerIcons = [
        'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"/></svg>',
        'about' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>',
        'services' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.36a1.7 1.7 0 0 0-1 .64 1.7 1.7 0 0 0-.36 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.24 15a1.7 1.7 0 0 0-.64-1 1.7 1.7 0 0 0-1.1-.36H2.4v-4h.09A1.7 1.7 0 0 0 4 8.6a1.7 1.7 0 0 0-.34-1.87l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8.36 4.24a1.7 1.7 0 0 0 1-.64 1.7 1.7 0 0 0 .36-1.1V2.4h4v.09A1.7 1.7 0 0 0 14.76 4a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.76 8.36c.15.38.38.72.68 1 .3.28.69.43 1.1.44h.06v4h-.09A1.7 1.7 0 0 0 20 14.84c-.16.05-.36.1-.6.16Z"/></svg>',
        'industries' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V9l5 3V9l5 3V5h4v16"/><path d="M8 16h1M12 16h1M17 10h2"/></svg>',
        'projects' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7h6v14M10 21V3h6v18M16 21v-9h4v9"/></svg>',
        'clients' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2 21v-2a6 6 0 0 1 12 0v2M12 21v-2a6 6 0 0 1 10-4.5"/></svg>',
        'careers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></svg>',
        'contact' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
    ];
@endphp
<style id="unifco-shared-site-header-style">
.site-header{position:sticky!important;top:0!important;z-index:90!important;background:rgba(255,255,255,.98)!important;border-bottom:1px solid #e7ebf0!important;backdrop-filter:blur(14px)!important;box-shadow:0 1px 4px rgba(7,31,77,.04)!important;height:auto!important}
.site-header .nav{width:min(1280px,94%)!important;max-width:1280px!important;min-height:76px!important;height:auto!important;margin-inline:auto!important;padding:0!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:20px!important;direction:ltr!important}
.site-header .brand-link{order:1!important;display:flex!important;align-items:center!important;gap:9px!important;margin:0!important;padding:0!important;direction:ltr!important;text-decoration:none!important;flex:0 0 auto!important}
.site-header .site-logo-frame{width:42px!important;height:50px!important;overflow:hidden!important;flex:0 0 42px!important}.site-header .site-logo{width:42px!important;height:65px!important;object-fit:cover!important;object-position:center top!important;display:block!important}
.site-header .brand-copy{display:flex!important;flex-direction:column!important;line-height:.88!important}.site-header .brand-copy strong{font-family:Inter,Arial,sans-serif!important;font-size:24px!important;line-height:1!important;letter-spacing:.03em!important;color:#071f4d!important;font-weight:800!important}.site-header .brand-copy small{margin-top:5px!important;font-family:Inter,Arial,sans-serif!important;font-size:6px!important;line-height:1!important;font-weight:900!important;letter-spacing:.16em!important;color:#ce122d!important}
.site-header .nav-actions{order:2!important;display:flex!important;align-items:center!important;gap:9px!important;direction:ltr!important;flex:0 0 auto!important;margin:0!important;padding:0!important}.site-header .nav-actions .lang{order:1!important}.site-header .nav-actions .header-btn:not(.red){order:2!important}.site-header .nav-actions .header-btn.red{order:3!important}
.site-header .header-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:42px!important;padding:10px 18px!important;border:1px solid transparent!important;border-radius:6px!important;background:#071f4d!important;color:#fff!important;font-size:11px!important;font-weight:900!important;text-decoration:none!important;white-space:nowrap!important}.site-header .header-btn.red{background:#ce122d!important}.site-header .lang{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:42px!important;padding:9px 13px!important;border:1px solid #ccd4df!important;border-radius:6px!important;font-size:10px!important;font-weight:900!important;color:#071f4d!important;background:#fff!important;text-decoration:none!important}
.site-header .nav-links{order:3!important;margin-left:auto!important;display:flex!important;align-items:stretch!important;justify-content:flex-end!important;gap:20px!important;direction:rtl!important;overflow:visible!important;min-width:0!important}.site-header .nav-links>a{position:relative!important;display:inline-flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:4px!important;min-width:54px!important;padding:9px 2px 8px!important;color:#20324e!important;text-decoration:none!important;font-size:11px!important;line-height:1.15!important;font-weight:800!important;white-space:nowrap!important;text-align:center!important;transition:color .18s ease,transform .18s ease!important}.site-header .nav-links>a:hover{color:#071f4d!important;transform:translateY(-1px)!important}.site-header .nav-links>a:first-child:after,.site-header .nav-links>a:hover:after{content:""!important;position:absolute!important;inset-inline:5px!important;bottom:0!important;height:2px!important;background:#ce122d!important}
.site-header .nav-icon{display:grid!important;place-items:center!important;width:21px!important;height:21px!important;color:currentColor!important}.site-header .nav-icon svg{display:block!important;width:21px!important;height:21px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.7!important;stroke-linecap:round!important;stroke-linejoin:round!important}.site-header .nav-label{display:block!important;white-space:nowrap!important;font-weight:800!important}
.site-header .menu-toggle{order:4!important;display:none!important;border:0!important;background:#071f4d!important;color:#fff!important;width:42px!important;height:42px!important;border-radius:7px!important;font-size:20px!important;cursor:pointer!important}.site-header .mobile-menu{display:none!important;padding:0 0 14px!important;direction:rtl!important}.site-header .mobile-menu.open{display:grid!important;grid-template-columns:1fr 1fr!important;gap:7px!important}.site-header .mobile-menu a{padding:10px 12px!important;background:#f5f7fa!important;border-radius:7px!important;color:#071f4d!important;font-size:11px!important;font-weight:800!important;text-decoration:none!important}
@media(max-width:1180px){.site-header .nav{gap:12px!important}.site-header .nav-links{gap:12px!important}.site-header .nav-links>a{font-size:10px!important;min-width:48px!important}.site-header .nav-icon,.site-header .nav-icon svg{width:18px!important;height:18px!important}.site-header .header-btn{padding-inline:14px!important}}
@media(max-width:1030px){.site-header .nav-links{display:none!important}.site-header .menu-toggle{display:grid!important;place-items:center!important;margin-left:auto!important}.site-header .nav{min-height:70px!important}}
@media(max-width:700px){.site-header .nav{min-height:64px!important;gap:10px!important}.site-header .brand-copy strong{font-size:20px!important}.site-header .site-logo-frame{width:35px!important;height:42px!important;flex-basis:35px!important}.site-header .site-logo{width:35px!important;height:54px!important}.site-header .lang,.site-header .header-btn:not(.red){display:none!important}.site-header .header-btn.red{min-height:38px!important;padding:8px 12px!important;font-size:10px!important}}
</style>
<header class="top site-header" data-shared-site-header="1">
<div class="wrap nav">
<a class="brand-link" href="{{ $headerBase }}"><span class="site-logo-frame"><img class="site-logo" src="{{ route('brand.logo') }}" alt="UNIFCO"></span><span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>
<div class="nav-actions"><a class="lang" href="{{ $languageUrl }}" data-language-switch="{{ $languageTarget }}">{{ strtoupper($languageTarget) }}</a><a class="header-btn" href="{{ route('login') }}">{{ $headerHome['login'] }}</a><a class="header-btn red" href="{{ route('public.request-service') }}">{{ $headerHome['request'] }}</a></div>
<nav class="nav-links public-primary-nav" aria-label="{{ $headerLocale==='ar' ? 'التنقل الرئيسي' : 'Primary navigation' }}">
@foreach($headerItems as [$key,$target])<a href="{{ $headerBase.$target }}"><span class="nav-icon">{!! $headerIcons[$key] !!}</span><span class="nav-label">{{ $headerLabels[$key] }}</span></a>@endforeach
</nav>
<button class="menu-toggle" type="button" aria-label="Menu" data-shared-menu-toggle>☰</button>
</div>
<nav class="wrap mobile-menu" data-shared-mobile-menu>@foreach($headerItems as [$key,$target])<a href="{{ $headerBase.$target }}">{{ $headerLabels[$key] }}</a>@endforeach<a href="{{ route('login') }}">{{ $headerHome['login'] }}</a><a href="{{ route('public.request-service') }}">{{ $headerHome['request'] }}</a><a href="{{ $languageUrl }}" data-language-switch="{{ $languageTarget }}">{{ strtoupper($languageTarget) }}</a></nav>
</header>
<script id="unifco-shared-site-header-script">document.querySelectorAll('[data-shared-site-header]').forEach(function(h){const t=h.querySelector('[data-shared-menu-toggle]'),m=h.querySelector('[data-shared-mobile-menu]');if(!t||!m)return;t.addEventListener('click',function(){m.classList.toggle('open')});m.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){m.classList.remove('open')})})});</script>
