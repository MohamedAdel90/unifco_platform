<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','UNIFCO Platform')</title>
<style>
:root{font-family:Inter,Arial,sans-serif;color:#172033;background:#f5f7fb}
*{box-sizing:border-box}
body{margin:0}
.shell{display:grid;grid-template-columns:270px 1fr;min-height:100vh}
.side{background:#172b4d;color:#fff;padding:22px}
.side a{display:block;color:#dbe5f5;text-decoration:none;padding:9px 11px;border-radius:8px;margin:2px 0}
.side a:hover{background:#24446f}
.side .section{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#8fa7c7;margin:18px 10px 6px}
.brand{font-size:20px;font-weight:800;margin-bottom:18px}
.main{padding:28px;min-width:0}
.top,.page-head,.row{display:flex;justify-content:space-between;align-items:center;gap:16px}
.top{margin-bottom:24px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.stack{display:grid;gap:10px}
.card{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:18px;box-shadow:0 2px 10px #172b4d0a}
.metric{font-size:28px;font-weight:750;color:#1e315b}
.muted{color:#68758a}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;display:block;overflow-x:auto;margin-top:16px}
.table th,.table td,table th,table td{padding:11px;border-bottom:1px solid #edf0f5;text-align:left}
.pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef3fa}
.btn{border:0;background:#1e315b;color:#fff;padding:9px 13px;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block}
.btn.secondary{background:#eef3fa;color:#1e315b}
.notice{padding:10px;background:#e8f7ee;border-radius:8px}
.error{padding:10px;background:#fdecec;border-radius:8px;color:#9f1f1f}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:12px}
label{display:grid;gap:5px}
input,select,textarea{padding:9px;border:1px solid #ccd5e2;border-radius:7px;max-width:100%}
.menu-toggle{display:none;border:0;background:#1e315b;color:#fff;width:40px;height:40px;border-radius:8px;font-size:20px;cursor:pointer;line-height:1;flex:0 0 auto}
.overlay{position:fixed;inset:0;background:rgba(23,43,77,.5);z-index:40;display:none}
@media(max-width:850px){
  .shell{grid-template-columns:1fr}
  .side{position:fixed;top:0;left:0;bottom:0;width:270px;z-index:50;overflow-y:auto;transform:translateX(-100%);transition:transform .22s ease}
  .side.open{transform:translateX(0)}
  .side .brand{display:flex;justify-content:space-between;align-items:center}
  .side .brand .close-side{display:block;background:none;border:0;color:#8fa7c7;font-size:22px;cursor:pointer;padding:0}
  .overlay.show{display:block}
  .menu-toggle{display:inline-flex;align-items:center;justify-content:center}
  .main{padding:16px}
  .top,.page-head,.row{flex-wrap:wrap}
  .grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}
  .form-grid{grid-template-columns:1fr}
  table,table.table{display:block;overflow-x:auto;white-space:nowrap;border-radius:10px}
  .metric{font-size:24px}
}
</style>
</head>
<body>
<div class="overlay" id="side-overlay"></div>
<div class="shell">
<aside class="side" id="side-menu">
<div class="brand">UNIFCO Platform<button class="close-side" type="button" data-close-side aria-label="Close menu">×</button></div>
<a href="{{ route('dashboard') }}">Dashboard</a>
<div class="section">Business</div>
@foreach(['finance'=>'Finance','hr'=>'Human Resources','procurement'=>'Procurement','inventory'=>'Inventory','crm'=>'CRM','projects'=>'Projects','manufacturing'=>'Manufacturing','maintenance'=>'Maintenance','eam'=>'Enterprise Assets'] as $slug=>$label)<a href="{{ route('modules.index',$slug) }}">{{ $label }}</a>@endforeach
<a href="{{ route('finance.core.index') }}">Finance Core</a>
<a href="{{ route('manufacturing.operations.index') }}">Manufacturing Operations</a>
<a href="{{ route('maintenance.operations.index') }}">Maintenance &amp; EAM Operations</a>
<div class="section">Platform</div>
<a href="{{ route('reporting.executive') }}">Executive Reporting</a>
<a href="{{ route('platform.documents.index') }}">Documents</a>
<a href="{{ route('platform.notifications.index') }}">Notifications</a>
<a href="{{ route('workflow.approvals.index') }}">Approvals</a>
<a href="{{ route('admin.audit.index') }}">Audit Trail</a>
<a href="{{ route('admin.permissions.index') }}">Permissions</a>
<a href="{{ route('admin.api-tokens.index') }}">API Tokens</a>
</aside>
<main class="main">
<div class="top">
<div style="display:flex;align-items:center;gap:10px;min-width:0"><button class="menu-toggle" type="button" data-open-side aria-label="Open menu">☰</button><strong>@yield('heading','UNIFCO')</strong></div>
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">{{ auth()->user()->name }} · <form style="display:inline" method="POST" action="{{ route('logout') }}">@csrf<button class="btn">Logout</button></form></div>
</div>
@yield('content')
</main>
</div>
<script>
(function(){
  var menu=document.getElementById('side-menu'),ov=document.getElementById('side-overlay');
  function open(){menu.classList.add('open');ov.classList.add('show');document.body.style.overflow='hidden'}
  function close(){menu.classList.remove('open');ov.classList.remove('show');document.body.style.overflow=''}
  var o=document.querySelector('[data-open-side]'),c=document.querySelector('[data-close-side]');
  if(o)o.addEventListener('click',open);if(c)c.addEventListener('click',close);
  if(ov)ov.addEventListener('click',close);
})();
</script>
</body>
</html>