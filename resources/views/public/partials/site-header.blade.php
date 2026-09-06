@php
    $headerHome = $home ?? app(\App\Services\HomepageContentService::class)->getContent('ar');
    $headerBase = route($headerHome['lang']==='ar' ? 'public.home' : 'public.home.en');
    $headerTargets = ['#home','#about','#services','#industries','#process','#projects','#contact'];
@endphp
<header class="top site-header">
<div class="wrap nav">
<a class="brand-link" href="{{ $headerBase }}"><span class="site-logo-frame"><img class="site-logo" src="{{ route('brand.logo') }}" alt="UNIFCO"></span><span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>
<nav class="nav-links">@foreach($headerHome['nav'] as $i=>$item)<a href="{{ $headerBase.($headerTargets[$i] ?? '#contact') }}">{{ $item }}</a>@endforeach</nav>
<div class="nav-actions"><a class="lang" href="{{ $headerHome['lang']==='ar' ? route('public.home.en') : route('public.home') }}">{{ $headerHome['lang']==='ar' ? 'EN' : 'AR' }}</a><a class="header-btn" href="{{ route('login') }}">{{ $headerHome['login'] }}</a><a class="header-btn red" href="{{ route('public.request-service') }}">{{ $headerHome['request'] }}</a></div>
<button class="menu-toggle" id="menu-toggle" type="button" aria-label="Menu">☰</button>
</div><nav class="wrap mobile-menu" id="mobile-menu">@foreach($headerHome['nav'] as $i=>$item)<a href="{{ $headerBase.($headerTargets[$i] ?? '#contact') }}">{{ $item }}</a>@endforeach<a href="{{ route('login') }}">{{ $headerHome['login'] }}</a><a href="{{ route('public.request-service') }}">{{ $headerHome['request'] }}</a><a href="{{ $headerHome['lang']==='ar' ? route('public.home.en') : route('public.home') }}">{{ $headerHome['lang']==='ar' ? 'EN' : 'AR' }}</a></nav>
</header>
<script>document.addEventListener('DOMContentLoaded',()=>{const toggle=document.getElementById('menu-toggle'),menu=document.getElementById('mobile-menu');toggle?.addEventListener('click',()=>menu.classList.toggle('open'));menu?.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>menu.classList.remove('open')))});</script>
