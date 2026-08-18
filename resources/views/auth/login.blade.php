<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>UNIFCO | Sign in</title>
<style>
:root{font-family:Inter,Arial,sans-serif;color:#132137;background:#f8f9fb;--navy:#1e315b;--dark:#132137;--red:#ce122d;--surface:#fff;--muted:#68758a;--border:#dce3ec}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:linear-gradient(135deg,#fff 0%,#f8f9fb 55%,#eef2f7 100%)}
.login-shell{min-height:100vh;display:grid;grid-template-columns:minmax(360px,46%) 1fr}
.brand-panel{display:flex;align-items:center;justify-content:center;padding:56px;background:#fff;border-right:1px solid #e7ebf1;position:relative;overflow:hidden}
.brand-panel:before,.brand-panel:after{content:"";position:absolute;border:54px solid #f5f7fa;transform:rotate(30deg);width:330px;height:330px;opacity:.8}.brand-panel:before{left:-220px;top:80px}.brand-panel:after{right:-260px;bottom:70px}
.brand-content{position:relative;z-index:1;text-align:center;max-width:520px}.brand-logo{display:block;width:min(360px,76vw);max-height:540px;object-fit:contain;margin:0 auto}.brand-copy{margin-top:26px;color:var(--navy);font-size:14px;letter-spacing:.08em;text-transform:uppercase;font-weight:700}.brand-sub{margin:10px auto 0;color:var(--muted);max-width:390px;line-height:1.7}
.form-panel{display:flex;align-items:center;justify-content:center;padding:48px 28px}.card{width:min(470px,100%);background:#fff;border:1px solid #e3e8ef;border-radius:18px;padding:38px;box-shadow:0 22px 60px rgba(19,33,55,.12)}
.mobile-logo{display:none;width:150px;height:150px;object-fit:contain;margin:0 auto 22px}h1{margin:0;color:var(--dark);font-size:31px;letter-spacing:-.02em}p.lead{margin:8px 0 28px;color:var(--muted)}label.field{display:grid;gap:7px;margin:0 0 17px;font-weight:650;color:var(--dark);font-size:14px}input[type=email],input[type=password]{width:100%;padding:13px 14px;border:1px solid var(--border);border-radius:9px;font:inherit;color:var(--dark);outline:none;background:#fff}input:focus{border-color:var(--navy);box-shadow:0 0 0 3px rgba(30,49,91,.10)}
.row{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:0 0 22px}.remember{display:flex;align-items:center;gap:8px;font-size:14px;color:#4c596d}.remember input{accent-color:var(--red)}
button{width:100%;padding:13px 16px;border:0;border-radius:9px;background:var(--red);color:#fff;font-size:15px;font-weight:750;cursor:pointer;box-shadow:0 8px 20px rgba(206,18,45,.18)}button:hover{filter:brightness(.96)}
.err{padding:11px 13px;border-radius:9px;background:#fdecec;color:#9f1f1f;border:1px solid #f7caca;margin:0 0 20px;font-size:14px}.secure{margin:22px 0 0;text-align:center;color:#8993a2;font-size:12px}.accent{height:4px;width:52px;background:var(--red);border-radius:999px;margin:0 0 20px}
@media(max-width:850px){.login-shell{grid-template-columns:1fr}.brand-panel{display:none}.form-panel{padding:30px 18px}.card{padding:30px 24px}.mobile-logo{display:block}h1{text-align:center;font-size:28px}.lead{text-align:center!important}.accent{margin-left:auto;margin-right:auto}.row{align-items:flex-start}}
</style>
</head>
<body>
<div class="login-shell">
<section class="brand-panel" aria-label="UNIFCO brand">
  <div class="brand-content">
    <img class="brand-logo" src="{{ route('brand.logo') }}" alt="UNIFCO — One Facility Shop">
    <div class="brand-copy">UNIFCO Enterprise Platform</div>
    <p class="brand-sub">One connected workspace for finance, operations, people, projects, assets and enterprise services.</p>
  </div>
</section>
<section class="form-panel">
  <form class="card" method="POST" action="{{ route('login.store') }}">
    @csrf
    <img class="mobile-logo" src="{{ route('brand.logo') }}" alt="UNIFCO — One Facility Shop">
    <div class="accent"></div>
    <h1>Welcome back</h1>
    <p class="lead">Sign in to the UNIFCO business platform.</p>
    @if($errors->any())<div class="err" role="alert">{{ $errors->first() }}</div>@endif
    <label class="field">Email address
      <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
    </label>
    <label class="field">Password
      <input name="password" type="password" autocomplete="current-password" required>
    </label>
    <div class="row">
      <label class="remember"><input type="checkbox" name="remember" value="1"> Remember me</label>
      <span style="font-size:13px;color:#68758a">Secure access</span>
    </div>
    <button type="submit">Sign in</button>
    <p class="secure">© {{ date('Y') }} UNIFCO · One Facility Shop</p>
  </form>
</section>
</div>
</body>
</html>
