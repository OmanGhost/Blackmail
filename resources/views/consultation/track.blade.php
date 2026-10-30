@extends('layouts.app',['title'=>'متابعة طلبك'])
@section('content')
<section class="container-page py-12"><div class="mx-auto max-w-3xl space-y-6">
<div><span class="eyebrow">طلباتك في مكان واحد</span><h1 class="section-title">متابعة طلبك</h1><p class="mt-3">رقم الطلب وحالته ورسائل الفريق، من هذه الصفحة مباشرة.</p></div>
@if($memberRequests->isNotEmpty())
<div class="card"><h2 class="text-lg font-bold">اختر من طلبات حسابك</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($memberRequests as $memberRequest)<a href="{{ route('account.requests.show', $memberRequest) }}" class="flex items-center justify-between gap-3 rounded-xl border p-4 {{ $case?->id === $memberRequest->id ? 'border-brand bg-blue-50 text-navy' : '' }}"><b>طلب {{ $memberRequest->request_number }}</b><span class="text-sm">{{ $memberRequest->status_label }} ←</span></a>@endforeach</div></div>
@endif
@if($case)
<div class="card">
    <div class="flex flex-wrap items-start justify-between gap-5"><div><p class="text-sm">رقم الطلب الموحد</p><div class="mt-2 flex items-center gap-3"><strong dir="ltr" class="font-mono text-4xl text-navy dark:text-white">{{ $case->request_number }}</strong><button type="button" data-copy="{{ $case->request_number }}" class="rounded-xl border px-4 py-2 text-sm font-bold">نسخ الرقم</button></div></div><span class="rounded-full bg-blue-50 px-5 py-3 font-bold text-brand">{{ $case->status_label }}</span></div>
    <div class="mt-6 grid gap-4 border-t pt-5 text-sm sm:grid-cols-2"><p>تاريخ الطلب: <b>{{ $case->created_at->format('Y-m-d H:i') }}</b></p><p>آخر تحديث: <b>{{ $case->updated_at->format('Y-m-d H:i') }}</b></p></div>
    <div class="mt-6 rounded-2xl bg-slate-50 p-5 dark:bg-slate-900"><h2 class="font-bold">العودة للطلب بضغطة واحدة</h2><p class="mt-2 text-sm leading-7">احفظ رابط المتابعة الخاص للرجوع من أي جهاز دون كتابة الرمز. الرابط صالح لمدة 30 يومًا ويتيح فتح المحادثة؛ احتفظ به لنفسك.</p><div class="mt-4 flex flex-wrap gap-3"><button type="button" data-copy="{{ $accessLink }}" class="rounded-xl bg-brand px-5 py-3 font-bold text-white">نسخ رابط المتابعة الخاص</button>@auth<a href="{{ route('account') }}" class="rounded-xl border px-5 py-3 font-bold">جميع طلباتي</a>@endauth</div></div>
</div>
<details class="card"><summary class="cursor-pointer font-bold">تفاصيل الطلب الذي أرسلته</summary><p class="mt-4 whitespace-pre-line leading-8">{{ $case->description }}</p></details>
<div class="card"><h2 class="text-xl font-bold">المحادثة مع فريق الدعم</h2>
@if(session('success'))<p role="status" class="mt-4 text-success">{{ session('success') }}</p>@endif
<div class="mt-6 space-y-4">@forelse($case->messages as $message)<article class="rounded-2xl border p-5 {{ $message->sender === 'staff' ? 'bg-blue-50 text-slate-900' : '' }}"><div class="flex flex-wrap justify-between gap-2"><b>{{ $message->sender === 'staff' ? 'فريق الدعم' : 'أنت' }}</b><time class="text-xs">{{ $message->created_at->format('Y-m-d H:i') }}</time></div><p class="mt-3 whitespace-pre-line">{{ $message->body }}</p></article>@empty<p class="rounded-xl bg-slate-50 p-5 dark:bg-slate-900">تم استلام طلبك وهو بانتظار رد الفريق. سيظهر الرد هنا.</p>@endforelse</div>
@if(! $case->isEnded())<form method="post" action="{{ route('consultation.reply') }}" class="mt-6">@csrf<label class="block font-bold">رسالتك للفريق<textarea name="body" required minlength="3" maxlength="5000" rows="5" class="mt-3 w-full rounded-xl border p-3" placeholder="أضف استفسارك أو تحديثًا على الحالة">{{ old('body') }}</textarea></label>@error('body')<p class="text-danger">{{ $message }}</p>@enderror<button class="mt-4 rounded-xl bg-brand px-6 py-3 font-bold text-white">إرسال الرسالة</button></form>@else<p class="mt-6 font-bold">{{ $case->status_label }} — يمكنك قراءة المحادثة السابقة.</p>@endif
</div>
@include('consultation.closure-controls')
<form method="post" action="{{ route('consultation.forget') }}">@csrf<button class="text-sm underline">إخفاء الطلب من هذه الجلسة</button></form>
@else
<div class="card"><h2 class="text-xl font-bold">الوصول إلى طلب سابق</h2><p class="mt-3 leading-8">إن حفظت رابط المتابعة الخاص، افتحه للوصول مباشرة. إذا قدمت الطلب وأنت مسجل الدخول، ستجده في حسابك.</p>@guest<a href="{{ route('login') }}" class="mt-5 inline-flex rounded-xl bg-brand px-6 py-3 font-bold text-white">تسجيل الدخول وعرض طلباتي</a>@endguest</div>
@if($searched)<p role="alert" class="rounded-xl bg-red-50 p-4 text-danger">لم نعثر على طلب بهذا الرمز. تحقق منه وحاول مجددًا.</p>@endif
@endif
<details class="card" @if($errors->has('tracking_code')) open @endif><summary class="cursor-pointer font-bold">{{ $case ? 'فتح طلب آخر برمز سري' : 'لدي رمز دخول قديم' }}</summary><p class="mt-4 text-sm leading-7">ألصق رمز الدخول السري (12 حرفًا ورقمًا) الذي حصلت عليه عند إرسال الطلب. هذا مختلف عن رقم الطلب الرقمي.</p><form method="post" action="{{ route('consultation.track.lookup') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">@csrf<input aria-label="رمز الدخول السري" name="tracking_code" value="{{ old('tracking_code') }}" required class="min-w-0 flex-1 rounded-xl border p-4 text-center font-mono" placeholder="ألصق الرمز هنا" autocomplete="off"><button class="rounded-xl bg-brand px-6 py-4 font-bold text-white">فتح الطلب</button></form>@error('tracking_code')<p role="alert" class="mt-3 text-danger">{{ $message }}</p>@enderror</details>
<p id="copy-status" role="status" class="text-center text-sm text-brand"></p>
</div></section>
@include('consultation.copy-script')
@endsection
