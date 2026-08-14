<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'UNIFCO Platform')</title>
    <style>
        :root{font-family:Inter,Arial,sans-serif;color:#172033;background:#f5f7fb}*{box-sizing:border-box}body{margin:0}.shell{display:grid;grid-template-columns:250px 1fr;min-height:100vh}.side{background:#172b4d;color:#fff;padding:24px}.side a{display:block;color:#dbe5f5;text-decoration:none;padding:10px 12px;border-radius:8px;margin:2px 0}.side a:hover{background:#24446f}.brand{font-size:20px;font-weight:700;margin-bottom:22px}.main{padding:28px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}.card{background:white;border:1px solid #e3e8ef;border-radius:12px;padding:18px;box-shadow:0 2px 10px #172b4d0a}.metric{font-size:30px;font-weight:700;color:#1e315b}.table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden}.table th,.table td{padding:12px;border-bottom:1px solid #edf0f5;text-align:left}.pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef3fa}.btn{border:0;background:#1e315b;color:#fff;padding:9px 13px;border-radius:8px;cursor:pointer}@media(max-width:800px){.shell{grid-template-columns:1fr}.side{display:none}}
    </style>
</head>
<body><div class="shell"><aside class="side"><div class="brand">UNIFCO Platform</div>
<a href="{{ route('dashboard') }}">Dashboard</a>
@foreach(['finance'=>'Finance','hr'=>'Human Resources','procurement'=>'Procurement','inventory'=>'Inventory','crm'=>'CRM','projects'=>'Projects','manufacturing'=>'Manufacturing','maintenance'=>'Maintenance','eam'=>'Enterprise Assets'] as $slug=>$label)<a href="{{ route('modules.index',$slug) }}">{{ $label }}</a>@endforeach
</aside><main class="main"><div class="top"><div><strong>@yield('heading','UNIFCO')</strong></div><div>{{ auth()->user()->name }} · <form style="display:inline" method="POST" action="{{ route('logout') }}">@csrf<button class="btn">Logout</button></form></div></div>@yield('content')</main></div></body></html>
