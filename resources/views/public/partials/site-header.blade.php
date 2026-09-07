@php
    $headerHome = $home ?? app(\App\Services\HomepageContentService::class)->getContent($locale ?? 'ar');
    $headerLocale = $headerHome['lang'] ?? ($locale ?? 'ar');
    $headerBase = route($headerLocale === 'ar' ? 'public.home' : 'public.home.en');
    $headerTargets = ['#home','#about','#services','#industries','#process','#projects','#contact'];
@endphp
<style id="unifco-shared-site-header-style">
.site-header{position:sticky;top:0;z-index:90;background:rgba(255,255,255,.97);border-bottom:1px solid #e7ebf0;backdrop-filter:blur(14px);box-shadow:0 1px 4px rgba(7,31,77,.04)}
.site-header .nav{width:min(1280px,94%);min-height:76px;margin-inline:auto;display:flex;align-items:center;gap:20px;direction:ltr}
.site-header .brand-link{display:flex;align-items:center;gap:9px;margin-right:auto;direction:ltr;text-decoration:none;flex:0 0 auto}
.site-header .site-logo-frame{width:42px;height:50px;overflow:hidden;flex:0 0 42px}.site-header .site-logo{width:42px;height:65px;object-fit:cover;object-position:center top;display:block}
.site-header .brand-copy{display:flex;flex-direction:column;line-height:.88}.site-header .brand-copy strong{font-family:Inter,Arial,sans-serif;font-size:24px;letter-spacing:.03em;color:#071f4d;font-weight:800}.site-header .brand-copy small{margin-top:5px;font-family:Inter,Arial,sans-serif;font-size:6px;font-weight:900;letter-spacing:.16em;color:#ce122d}
.site-header .nav-links{display:flex;align-items:center;gap:23px;direction:{{ $headerHome['dir'] ?? 'rtl' }}}.site-header .nav-links a{position:relative;font-size:12px;font-weight:800;color:#25354d;white-space:nowrap;text-decoration:none}.site-header .nav-links a:first-child:after,.site-header .nav-links a:hover:after{content:"";position:absolute;inset-inline:0;bottom:-12px;height:2px;background:#ce122d}
.site-header .nav-actions{display:flex;align-items:center;gap:9px;direction:ltr}.site-header .header-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 18px;border:1px solid transparent;border-radius:6px;background:#071f4d;color:#fff;font-size:11px;font-weight:900;text-decoration:none;white-space:nowrap}.site-header .header-btn.red{background:#ce122d}.site-header .lang{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 13px;border:1px solid #ccd4df;border-radius:6px;font-size:10px;font-weight:900;color:#071f4d;background:#fff;text-decoration:none}
.site-header .menu-toggle{display:none;border:0;background:#071f4d;color:#fff;width:42px;height:42px;border-radius:7px;font-size:20px;cursor:pointer}.site-header .mobile-menu{display:none;padding:0 0 14px;direction:{{ $headerHome['dir'] ?? 'rtl' }}}.site-header .mobile-menu.open{display:grid;grid-template-columns:1fr 1fr;gap:7px}.site-header .mobile-menu a{padding:10px 12px;background:#f5f7fa;border-radius:7px;color:#071f4d;font-size:11px;font-weight:800;text-decoration:none}
@media(max-width:1180px){.site-header .nav-links{gap:14px}.site-header .nav-links a{font-size:10.5px}.site-header .header-btn{padding-inline:14px}}
@media(max-width:1030px){.site-header .nav-links{display:none}.site-header .menu-toggle{display:grid;place-items:center}.site-header .nav{min-height:70px}}
@media(max-width:700px){.site-header .nav{min-height:64px;gap:10px}.site-header .brand-copy strong{font-size:20px}.site-header .site-logo-frame{width:35px;height:42px;flex-basis:35px}.site-header .site-logo{width:35px;height:54px}.site-header .lang,.site-header .header-btn:not(.red){display:none}.site-header .header-btn.red{min-height:38px;padding:8px 12px;font-size:10px}}
</style>
<header class="top site-header" data-shared-site-header="1">
<div class="wrap nav">
<a class="brand-link" href="{{ $headerBase }}"><span class="site-logo-frame"><img class="site-logo" src="{{ route('brand.logo') }}" alt="UNIFCO"></span><span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>
<nav class="nav-links">@foreach($headerHome['nav'] as $i=>$item)<a href="{{ $headerBase.($headerTargets[$i] ?? '#contact') }}">{{ $item }}</a>@endforeach</nav>
<div class="nav-actions"><a class="lang" href="{{ $headerLocale==='ar' ? route('public.home.en') : route('public.home') }}">{{ $headerLocale==='ar' ? 'EN' : 'AR' }}</a><a class="header-btn" href="{{ route('login') }}">{{ $headerHome['login'] }}</a><a class="header-btn red" href="{{ route('public.request-service') }}">{{ $headerHome['request'] }}</a></div>
<button class="menu-toggle" type="button" aria-label="Menu" data-shared-menu-toggle>☰</button>
</div><nav class="wrap mobile-menu" data-shared-mobile-menu>@foreach($headerHome['nav'] as $i=>$item)<a href="{{ $headerBase.($headerTargets[$i] ?? '#contact') }}">{{ $item }}</a>@endforeach<a href="{{ route('login') }}">{{ $headerHome['login'] }}</a><a href="{{ route('public.request-service') }}">{{ $headerHome['request'] }}</a><a href="{{ $headerLocale==='ar' ? route('public.home.en') : route('public.home') }}">{{ $headerLocale==='ar' ? 'EN' : 'AR' }}</a></nav>
</header>
<script id="unifco-shared-site-header-script">document.querySelectorAll('[data-shared-site-header]').forEach(function(h){const t=h.querySelector('[data-shared-menu-toggle]'),m=h.querySelector('[data-shared-mobile-menu]');if(!t||!m)return;t.addEventListener('click',function(){m.classList.toggle('open')});m.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){m.classList.remove('open')})})});</script>
