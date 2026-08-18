<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','UNIFCO Platform')</title>
<style>
:root{font-family:Inter,Arial,sans-serif;color:#172033;background:#f8f9fb;--navy:#1e315b;--dark:#132137;--red:#ce122d;--surface:#fff;--surface-alt:#f8f9fb;--muted:#68758a}
*{box-sizing:border-box}body{margin:0;background:var(--surface-alt)}
.shell{display:grid;grid-template-columns:280px 1fr;min-height:100vh}
.side{background:linear-gradient(180deg,#132137 0%,#1e315b 100%);color:#fff;padding:20px 18px;box-shadow:4px 0 18px rgba(19,33,55,.08)}
.brand{margin-bottom:20px;position:relative}.brand-logo-wrap{display:flex;align-items:center;justify-content:center;background:#fff;border-radius:14px;padding:12px;min-height:126px;box-shadow:0 8px 24px rgba(0,0,0,.12)}.brand-logo{width:155px;height:105px;object-fit:contain;display:block}.close-side{display:none}
.side a{display:block;color:#e6ebf3;text-decoration:none;padding:10px 12px;border-radius:9px;margin:3px 0;border-left:3px solid transparent;transition:background .15s ease,border-color .15s ease,color .15s ease}.side a:hover{background:rgba(255,255,255,.09);border-left-color:var(--red);color:#fff}
.side .section{font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#9aaac1;margin:20px 12px 7px;font-weight:700}
.main{padding:28px;min-width:0}.top,.page-head,.row{display:flex;justify-content:space-between;align-items:center;gap:16px}.top{margin:-28px -28px 26px;padding:17px 28px;background:#fff;border-bottom:1px solid #e6eaf0;min-height:70px}.top strong{color:var(--dark);font-size:18px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}.stack{display:grid;gap:10px}.card{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:18px;box-shadow:0 3px 14px rgba(19,33,55,.05)}.metric{font-size:28px;font-weight:750;color:var(--navy)}.muted{color:var(--muted)}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;display:block;overflow-x:auto;margin-top:16px}table th,table td{padding:11px;border-bottom:1px solid #edf0f5;text-align:left}.pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef3fa}.btn{border:0;background:var(--navy);color:#fff;padding:9px 13px;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block;font-weight:650}.btn:hover{filter:brightness(.95)}.btn.secondary{background:#eef3fa;color:var(--navy)}.notice{padding:10px;background:#e8f7ee;border-radius:8px}.error{padding:10px;background:#fdecec;border-radius:8px;color:#9f1f1f}.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:12px}label{display:grid;gap:5px}input,select,textarea{padding:9px;border:1px solid #ccd5e2;border-radius:7px;max-width:100%}input:focus,select:focus,textarea:focus{outline:none;border-color:var(--navy);box-shadow:0 0 0 3px rgba(30,49,91,.08)}
.menu-toggle{display:none;border:0;background:var(--navy);color:#fff;width:40px;height:40px;border-radius:8px;font-size:20px;cursor:pointer;line-height:1;flex:0 0 auto}.overlay{position:fixed;inset:0;background:rgba(19,33,55,.55);z-index:40;display:none}.user-name{color:var(--navy);font-weight:650}.logout-btn{background:var(--red)}
@media(max-width:850px){.shell{grid-template-columns:1fr}.side{position:fixed;top:0;left:0;bottom:0;width:280px;z-index:50;overflow-y:auto;transform:translateX(-100%);transition:transform .22s ease}.side.open{transform:translateX(0)}.brand{padding-right:40px}.close-side{display:block;position:absolute;right:0;top:0;background:none;border:0;color:#fff;font-size:28px;cursor:pointer;padding:4px 6px}.brand-logo-wrap{min-height:112px}.brand-logo{height:92px}.overlay.show{display:block}.menu-toggle{display:inline-flex;align-items:center;justify-content:center}.main{padding:16px}.top{margin:-16px -16px 20px;padding:13px 16px}.top,.page-head,.row{flex-wrap:wrap}.grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}.form-grid{grid-template-columns:1fr}table,table.table{display:block;overflow-x:auto;border-radius:10px}table th,table td,table.table th,table.table td{padding:9px 8px;font-size:13px}.metric{font-size:24px}}
</style>
</head>
<body>
<div class="overlay" id="side-overlay"></div>
<div class="shell">
<aside class="side" id="side-menu">
<div class="brand">
  <div class="brand-logo-wrap"><img class="brand-logo" src="{{ route('brand.logo') }}" alt="UNIFCO — One Facility Shop"></div>
  <button class="close-side" type="button" data-close-side aria-label="Close menu">×</button>
</div>
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
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap"><span class="user-name">{{ auth()->user()->name }}</span><form style="display:inline" method="POST" action="{{ route('logout') }}">@csrf<button class="btn logout-btn">Logout</button></form></div>
</div>
@yield('content')
</main>
</div>
<script>
(function(){var menu=document.getElementById('side-menu'),ov=document.getElementById('side-overlay');function open(){menu.classList.add('open');ov.classList.add('show');document.body.style.overflow='hidden'}function close(){menu.classList.remove('open');ov.classList.remove('show');document.body.style.overflow=''}var o=document.querySelector('[data-open-side]'),c=document.querySelector('[data-close-side]');if(o)o.addEventListener('click',open);if(c)c.addEventListener('click',close);if(ov)ov.addEventListener('click',close)})();
</script>
</body>
</html>