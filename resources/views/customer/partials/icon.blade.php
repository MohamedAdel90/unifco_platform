@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'actions' => '<path d="M12 3v10"/><path d="m8 9 4 4 4-4"/><path d="M5 16v3h14v-3"/>',
        'activity' => '<path d="M3 12h4l2-6 4 12 2-6h6"/>',
        'requests' => '<path d="M6 3h9l3 3v15H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
        'work-orders' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9 7 7M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/>',
        'visits' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 15h3"/>',
        'maintenance' => '<path d="M14.7 6.3a4 4 0 0 0-5-5L12 4 9 7 6.3 4.3a4 4 0 0 0 5 5L4 16.6V20h3.4l7.3-7.3a4 4 0 0 0 5-5L17 10l-3-3 2.7-2.7"/>',
        'parts' => '<path d="M4 7 12 3l8 4-8 4z"/><path d="M4 7v10l8 4 8-4V7M12 11v10"/>',
        'sites' => '<path d="M4 21V5l8-3 8 3v16M9 9h1M14 9h1M9 13h1M14 13h1M10 21v-4h4v4"/>',
        'assets' => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M8 6V3h8v3M8 12h8M12 9v6"/>',
        'quotations' => '<path d="M6 2h12v20H6z"/><path d="M9 7h6M9 11h6M9 15h3"/>',
        'contracts' => '<path d="M5 3h14v18H5z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'sla' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'invoices' => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'reports' => '<path d="M4 19V9M10 19V5M16 19V12M22 19H2"/>',
        'documents' => '<path d="M6 2h8l4 4v16H6z"/><path d="M14 2v5h5M9 12h6M9 16h6"/>',
        'notifications' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'inbox' => '<path d="M3 5h18v14H3z"/><path d="m3 7 9 7 9-7"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 7h6M19 4v6"/>',
        'profile' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
    ];
@endphp
<svg class="ui-icon {{ $class ?? '' }}" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $paths[$name] ?? $paths['dashboard'] !!}</svg>
