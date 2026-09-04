@php
    $normalizeShowcaseText = static function ($value): string {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $value;
    };

    $projectSeen = [];
    $showcaseProjects = collect($home['showcase_projects'] ?? [])->filter(function ($project) use (&$projectSeen, $normalizeShowcaseText) {
        if (! is_array($project)) return false;

        $identity = implode('|', [
            $normalizeShowcaseText($project['title'] ?? ''),
            $normalizeShowcaseText($project['owner'] ?? ''),
            $normalizeShowcaseText($project['location'] ?? ''),
            $normalizeShowcaseText($project['year'] ?? ''),
        ]);

        if ($identity === '|||') {
            $identity = $normalizeShowcaseText(parse_url((string) ($project['image'] ?? ''), PHP_URL_PATH) ?: ($project['image'] ?? ''));
        }

        if ($identity === '' || isset($projectSeen[$identity])) return false;
        $projectSeen[$identity] = true;
        return true;
    })->values();

    $clientSeen = [];
    $showcaseClients = collect($home['showcase_clients'] ?? [])->filter(function ($client) use (&$clientSeen, $normalizeShowcaseText) {
        if (! is_array($client)) return false;

        $name = $normalizeShowcaseText($client[1] ?? '');
        $image = $normalizeShowcaseText(parse_url((string) ($client[0] ?? ''), PHP_URL_PATH) ?: ($client[0] ?? ''));
        $identity = $name !== '' ? 'name:'.$name : 'image:'.$image;

        if ($identity === 'image:' || isset($clientSeen[$identity])) return false;
        $clientSeen[$identity] = true;
        return true;
    })->values();
@endphp
<section class="projects-showcase" id="projects" style="--showcase-dir:{{ $home['dir'] }}">
<span class="showcase-anchor" id="unifco-project-showcase" aria-hidden="true"></span>
<div class="wrap">
<header class="showcase-heading"><div class="kicker">{{ $home['showcase_kicker'] }}</div><h2>{{ $home['showcase_title'] }}</h2><p>{{ $home['showcase_text'] }}</p></header>
<div class="showcase-metrics">
@foreach($home['showcase_metrics'] as $metric)<div class="showcase-metric"><span class="showcase-metric-icon">@include('public.partials.home-icon',['name'=>$metric[0]])</span><span><strong>{{ $metric[1] }}</strong><span>{{ $metric[2] }}</span></span></div>@endforeach
</div>
<div class="showcase-rail-shell" data-showcase-carousel>
<button class="showcase-arrow prev" type="button" data-showcase-prev aria-label="{{ $home['carousel_previous'] }}" aria-controls="project-showcase-rail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
<div class="showcase-rail" id="project-showcase-rail" data-showcase-rail tabindex="0">
@foreach($showcaseProjects as $project)<article class="showcase-project-card"><div class="showcase-project-media"><img src="{{ $project['image'] }}" alt="{{ $project['title'] }}" loading="lazy"><span class="showcase-project-year">{{ $project['year'] }}</span></div><div class="showcase-project-body"><h3>{{ $project['title'] }}</h3><span class="showcase-project-owner">{{ $project['owner'] }}</span><span class="showcase-project-location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $project['location'] }}</span><span class="showcase-project-tag">{{ $project['scope'] }}</span></div></article>@endforeach
</div>
<button class="showcase-arrow next" type="button" data-showcase-next aria-label="{{ $home['carousel_next'] }}" aria-controls="project-showcase-rail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
</div>
<div class="showcase-action"><a class="btn" href="#projects">{{ $home['projects_button'] }} <span aria-hidden="true">‹</span></a></div>
</div>
</section>
<section class="showcase-clients" id="clients" style="--showcase-dir:{{ $home['dir'] }}">
<div class="wrap">
<header class="showcase-heading"><h2>{{ $home['clients_title'] }}</h2><p>{{ $home['clients_text'] }}</p></header>
<div class="showcase-rail-shell" data-showcase-carousel>
<button class="showcase-arrow prev" type="button" data-showcase-prev aria-label="{{ $home['carousel_previous'] }}" aria-controls="client-showcase-rail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
<div class="showcase-rail client-rail" id="client-showcase-rail" data-showcase-rail tabindex="0">
@foreach($showcaseClients as $client)<article class="client-card"><img src="{{ $client[0] }}" alt="{{ $client[1] }}" loading="lazy"></article>@endforeach
<article class="client-card more"><strong>+</strong><span>{{ $home['more_clients'] }}</span></article>
</div>
<button class="showcase-arrow next" type="button" data-showcase-next aria-label="{{ $home['carousel_next'] }}" aria-controls="client-showcase-rail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
</div>
<div class="showcase-action"><a class="btn" href="#clients">{{ $home['all_clients'] }} <span aria-hidden="true">‹</span></a></div>
<aside class="emergency-banner" aria-labelledby="emergency-showcase-title">
<div class="emergency-visual"><img src="/images/home/projects/emergency-team.webp" alt="{{ $home['emergency_photo_alt'] }}" loading="lazy"><span class="emergency-siren"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M14 32V20a10 10 0 0 1 20 0v12M9 36h30M18 32V20a6 6 0 0 1 12 0v12M24 3v5M7 12l5 3M41 12l-5 3M4 25h6M38 25h6"/></svg></span></div>
<div class="emergency-copy"><h2 id="emergency-showcase-title">{{ $home['emergency_title'] }}</h2><p>{{ $home['emergency_text'] }}</p></div>
<div class="emergency-detail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13H2v5h4v-5H4ZM20 13h2v5h-4v-5h2ZM18 19c-1 2-3 2-5 2"/></svg><span><small>{{ $home['operations_support'] }}</small><strong>24/7</strong></span></div>
<a class="emergency-detail" href="tel:+966599402090"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-3 2c1.5 3 3 4.5 6 6l2-3 5 2v4c0 1-1 2-2 2C10 21 3 14 3 6c0-1 1-2 2-2Z"/></svg><span><small>{{ $home['contact_now'] }}</small><strong>0599402090</strong></span></a>
<a class="emergency-detail email" href="mailto:info@unifco.com"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg><span><small>{{ $home['email_us'] }}</small><strong>info@unifco.com</strong></span></a>
<div class="emergency-cta"><a class="btn red" href="{{ route('public.emergency') }}">{{ $home['emergency_button'] }} <span aria-hidden="true">⚡</span></a></div>
</aside>
</div>
</section>
