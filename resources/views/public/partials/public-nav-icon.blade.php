@switch($name)
@case('home')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"/></svg>@break
@case('about')<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>@break
@case('services')<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.36a1.7 1.7 0 0 0-1 .64 1.7 1.7 0 0 0-.36 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.24 15a1.7 1.7 0 0 0-.64-1 1.7 1.7 0 0 0-1.1-.36H2.4v-4h.09A1.7 1.7 0 0 0 4 8.6a1.7 1.7 0 0 0-.34-1.87l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8.36 4.24a1.7 1.7 0 0 0 1-.64 1.7 1.7 0 0 0 .36-1.1V2.4h4v.09A1.7 1.7 0 0 0 14.76 4a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.76 8.36c.15.38.38.72.68 1 .3.28.69.43 1.1.44h.06v4h-.09A1.7 1.7 0 0 0 20 14.84c-.16.05-.36.1-.6.16Z"/></svg>@break
@case('industries')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V9l5 3V9l5 3V5h4v16"/><path d="M8 16h1M12 16h1M17 10h2"/></svg>@break
@case('projects')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7h6v14M10 21V3h6v18M16 21v-9h4v9"/></svg>@break
@case('clients')<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2 21v-2a6 6 0 0 1 12 0v2M12 21v-2a6 6 0 0 1 10-4.5"/></svg>@break
@case('careers')<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></svg>@break
@case('contact')<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>@break
@case('building')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V8l7-4 7 4v13M9 11h1M14 11h1M9 15h1M14 15h1"/></svg>@break
@case('quality')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.3 4.7 5.2.8-3.8 3.7.9 5.2-4.6-2.4-4.6 2.4.9-5.2-3.8-3.7 5.2-.8L12 3Z"/></svg>@break
@case('engineering')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14.7 6.3 3 3M4 20l6.4-6.4M6 4l4 4M4 6l4-4M13.2 13.2l4.5-4.5a3.2 3.2 0 0 0-4.5-4.5L8.7 8.7a3.2 3.2 0 0 0 4.5 4.5Z"/></svg>@break
@case('target')<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="m16 8 5-5M17 3h4v4"/></svg>@break
@case('innovation')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 21h4M8 14a6 6 0 1 1 8 0c-1.2 1-1.8 2.2-1.8 4H9.8c0-1.8-.6-3-1.8-4ZM12 2V1M4.9 4.9 4.2 4.2M19.1 4.9l.7-.7"/></svg>@break
@case('leaf')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4C11 4 5 8 5 15c0 2.5 1.8 4 4 4 7 0 11-6 11-15ZM4 21c3-5 7-8 12-10"/></svg>@break
@case('safety')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-4.8"/></svg>@break
@default<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2.5"/></svg>
@endswitch
