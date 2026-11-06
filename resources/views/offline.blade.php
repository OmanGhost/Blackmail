@php
    $local = config('platform.hotline.local');
    $international = config('platform.hotline.international');
    $tel = fn (string $phone) => preg_replace('/[^\d+]/', '', $phone);
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#102A43">
<title>{{ config('app.name') }}</title>
<style>
:root{color-scheme:light dark;--bg:#F3F6FA;--card:#fff;--text:#334155;--muted:#64748b;--navy:#102A43;--line:#e2e8f0}
@media (prefers-color-scheme:dark){:root{--bg:#020617;--card:#0f172a;--text:#e2e8f0;--muted:#94a3b8;--navy:#fff;--line:#1e293b}}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);color:var(--text);font-family:system-ui,-apple-system,"Segoe UI",Tahoma,Arial,sans-serif;line-height:1.8;padding:max(1.5rem,env(safe-area-inset-top)) 1rem max(1.5rem,env(safe-area-inset-bottom))}
main{width:min(100%,30rem);background:var(--card);border:1px solid var(--line);border-radius:1.4rem;padding:1.75rem;text-align:center}
.mark{display:inline-grid;place-items:center;width:3.5rem;height:3.5rem;border-radius:1rem;background:#102A43;color:#fff}
h1{margin:1rem 0 .25rem;font-size:1.35rem;color:var(--navy)}
p{margin:.25rem 0;color:var(--muted)}
.en{direction:ltr;font-size:.92rem}
.calls{display:grid;gap:.6rem;margin:1.4rem 0}
.call{display:flex;align-items:center;justify-content:center;gap:.6rem;min-height:3.25rem;border-radius:.9rem;background:#dc2626;color:#fff;font-weight:800;font-size:1.1rem;text-decoration:none}
.call.alt{background:transparent;color:var(--navy);border:1px solid var(--line);font-size:1rem}
button{font:inherit;min-height:3rem;width:100%;border-radius:.9rem;border:0;background:#102A43;color:#fff;font-weight:700;cursor:pointer}
@media (prefers-color-scheme:dark){button{background:#2563eb}}
small{display:block;margin-top:1rem;color:var(--muted);font-size:.8rem}
</style>
</head>
<body>
<main>
    <span class="mark" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-5"/></svg></span>
    <h1>لا يوجد اتصال بالإنترنت</h1>
    <p>تحقق من الاتصال ثم أعد المحاولة. ما زال بإمكانك الاتصال برقم المنصة.</p>
    <p class="en" lang="en">You are offline. Check your connection and try again, or call the platform.</p>
    <div class="calls">
        <a class="call" href="tel:{{ $tel($local) }}">اتصال من داخل عُمان <b dir="ltr">{{ $local }}</b></a>
        <a class="call alt" href="tel:{{ $tel($international) }}">من خارج عُمان <b dir="ltr">{{ $international }}</b></a>
    </div>
    <button type="button" onclick="location.reload()">إعادة المحاولة · Retry</button>
    <small>عند وجود خطر جسدي مباشر اتصل بخدمات الطوارئ المحلية.</small>
</main>
</body>
</html>
