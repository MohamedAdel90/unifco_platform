<!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>UNIFCO | UAT Password Reset</title>
<style>
:root{font-family:Inter,Arial,sans-serif;--navy:#06275c;--navy2:#071f4d;--red:#e20b24;--muted:#66758c;--line:#dce3ec;--soft:#f7f9fc}*{box-sizing:border-box}body{margin:0;background:var(--soft);color:var(--navy2);min-height:100vh;display:grid;place-items:center;padding:24px}.card{width:min(520px,100%);background:#fff;border:1px solid #e6ebf2;border-radius:18px;box-shadow:0 18px 48px rgba(16,44,88,.10);padding:34px}.logo{display:block;width:210px;height:92px;object-fit:contain;margin:0 auto}.accent{width:62px;height:4px;background:var(--red);border-radius:20px;margin:5px auto 22px}h1{text-align:center;font-size:26px;margin:0 0 8px}.sub{text-align:center;color:var(--muted);font-size:13px;line-height:1.6;margin-bottom:24px}.notice{background:#edf7f2;border:1px solid #cbe8da;color:#176b49;border-radius:9px;padding:11px 13px;margin-bottom:18px;font-size:12px}.error{background:#fff1f3;border:1px solid #facad1;color:#a11d31;border-radius:9px;padding:11px 13px;margin-bottom:18px;font-size:12px}.field{display:block;font-size:12px;font-weight:750;margin-bottom:16px}.field span{display:block;margin-bottom:7px}.field input{width:100%;height:46px;border:1px solid #d5dde8;border-radius:8px;padding:0 13px;font:inherit}.field input:focus{outline:0;border-color:#7891b5;box-shadow:0 0 0 3px rgba(6,39,92,.06)}button{width:100%;height:48px;border:0;border-radius:8px;background:var(--red);color:#fff;font-weight:800;font-size:14px;cursor:pointer}.meta{margin-top:18px;background:#f8fafc;border:1px solid #edf1f5;border-radius:9px;padding:11px;color:#68758a;font-size:11px;line-height:1.55}.email{font-weight:800;color:var(--navy)}
</style>
</head>
<body>
<main class="card">
<img class="logo" src="{{ route('brand.logo') }}" alt="UNIFCO"><div class="accent"></div>
<h1>Set your password</h1>
<p class="sub">Secure one-time UAT password setup for <span class="email">{{ $email }}</span>.</p>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('uat-reset.update',['token'=>$token]) }}">@csrf
<label class="field"><span>New password</span><input name="password" type="password" autocomplete="new-password" minlength="10" required autofocus></label>
<label class="field"><span>Confirm new password</span><input name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required></label>
<button type="submit">Set Password</button>
</form>
<div class="meta">This link is single-use and expires {{ $expiresAt->toDayDateTimeString() }}. Completing it unlocks the account, revokes existing sessions, and records the action in the audit trail.</div>
</main>
</body>
</html>
