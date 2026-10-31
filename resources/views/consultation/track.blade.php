@extends('layouts.app',['title'=>app()->getLocale()==='en'?'Track your request':'متابعة طلبك'])
@section('content')
@php
    $en=app()->getLocale()==='en';
@endphp
<section class="tracking-workspace">
<div class="mb-7 flex flex-wrap items-center justify-between gap-4"><div><p class="text-sm text-slate-500">{{ config('app.name') }} / {{ $en?'Support':'الدعم والمساعدة' }}</p><h1 class="mt-2 text-3xl font-extrabold text-navy dark:text-white">{{ $en?'Track your request':'متابعة طلبك' }}</h1></div><div class="flex flex-wrap gap-3">@auth<a class="member-button border border-slate-200" href="{{ route('account') }}">{{ $en?'My requests':'طلباتي' }}</a>@endauth<a class="member-button member-button-primary" href="{{ route('consultation') }}">＋ {{ $en?'New request':'طلب جديد' }}</a></div></div>
@if(session('success'))<p role="status" class="mb-6 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>@endif
@if($case)
@php
    $pendingTasks=$case->tasks()->whereNull('completed_at')->count();
    $fileRequests=$case->evidenceRequests()->where('status','open')->where('expires_at','>',now())->count();
    $tabs=[
        'conversation'=>['☏',$en?'Conversation':'المحادثة',$case->messages()->count()],
        'files'=>['▣',$en?'Private submissions':'المعلومات والمرفقات',$case->isEnded()?0:$fileRequests],
        'plan'=>['☑',$en?'Follow-up plan':'خطة المتابعة',$pendingTasks],
        'details'=>['☷',$en?'Request details':'تفاصيل الطلب',0],
        'timeline'=>['◷',$en?'Activity timeline':'الخط الزمني',0],
        'access'=>['◇',$en?'Access & privacy':'الوصول والخصوصية',0],
        'finish'=>['☆',$case->isEnded()?($en?'Service rating':'تقييم الخدمة'):($en?'Close request':'إنهاء الطلب'),0],
    ];
@endphp
<div class="tracking-summary"><div><p class="text-sm text-slate-500">{{ $en?'Unified request number':'رقم الطلب الموحد' }}</p><div class="mt-2 flex items-center gap-4"><strong dir="ltr" class="font-mono text-4xl font-extrabold text-navy dark:text-white">{{ $case->request_number }}</strong><button type="button" data-copy="{{ $case->request_number }}" class="rounded-xl border px-3 py-2 text-xs font-bold">{{ $en?'Copy number':'نسخ الرقم' }}</button></div></div><div class="flex flex-wrap items-center gap-5"><div class="text-sm"><p class="text-xs text-slate-500">{{ $en?'Last updated':'آخر تحديث' }}</p><time class="mt-2 block font-semibold">{{ $case->updated_at->format('Y-m-d H:i') }}</time></div><span class="member-status status-{{ $case->status }}">{{ $case->status_label }}</span></div></div>
@if($fileRequests && !$case->isEnded() && $panel!=='files')<a href="{{ route('consultation.track',['panel'=>'files']) }}" class="tracking-callout"><div><b>{{ $en?'The specialist requested additional information':'طلب المختص معلومات أو مرفقات إضافية' }}</b><p class="mt-1 text-xs">{{ $en?'Open the private form when you are ready.':'افتح النموذج الخاص للاطلاع على المطلوب والإرسال عندما تكون مستعدًا.' }}</p></div><span class="shrink-0 text-sm font-bold">{{ $en?'View':'عرض المطلوب' }} ←</span></a>@endif
<div class="tracking-layout">
    <nav class="tracking-navigation" aria-label="{{ $en?'Request sections':'أقسام الطلب' }}">@foreach($tabs as $key=>[$icon,$label,$count])<a href="{{ route('consultation.track',['panel'=>$key]) }}" class="{{ $panel===$key?'is-active':'' }}" @if($panel===$key) aria-current="page" @endif><span aria-hidden="true">{{ $icon }}</span><span>{{ $label }}</span>@if($count)<small>{{ $count }}</small>@endif</a>@endforeach</nav>
    <div class="min-w-0 space-y-5" id="tracking-content">
    <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-extrabold text-navy dark:text-white">{{ $tabs[$panel][1] }}</h2><span class="text-xs text-slate-500">{{ $en?'Request':'طلب رقم' }} {{ $case->request_number }}</span></div>
    @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><x-form-errors/></div>@endif
    @if($panel==='conversation')
    <section class="tracking-panel"><div class="tracking-messages">@forelse($case->messages as $message)<article class="tracking-message {{ $message->sender==='staff'?'is-staff':'' }}"><div class="flex flex-wrap justify-between gap-3"><b class="text-sm">{{ $message->sender==='staff'?($en?'Support team':'فريق الدعم'):($en?'You':'أنت') }}</b><time class="text-xs opacity-70">{{ $message->created_at->format('Y-m-d H:i') }}</time></div><p class="mt-3 whitespace-pre-line text-sm leading-8">{{ $message->body }}</p></article>@empty<div class="py-10 text-center"><div class="text-4xl text-slate-300" aria-hidden="true">☏</div><h3 class="mt-4 font-bold">{{ $en?'Your request has been received':'تم استلام طلبك' }}</h3><p class="mt-3 text-sm text-slate-500">{{ $en?'Team replies will appear here. You can add a message below.':'ستظهر ردود الفريق هنا، ويمكنك إضافة رسالة من النموذج أدناه.' }}</p></div>@endforelse</div>
    @if(!$case->isEnded())<form method="post" action="{{ route('consultation.reply') }}" class="mt-6 border-t border-slate-100 pt-6 dark:border-slate-700">@csrf<label class="block text-sm font-bold">{{ $en?'Your message':'رسالتك للفريق' }}<textarea name="body" required minlength="3" maxlength="5000" rows="4" class="member-input mt-3" placeholder="{{ $en?'Write a question or update…':'اكتب استفسارًا أو تحديثًا على حالتك…' }}">{{ old('body') }}</textarea></label><button class="member-button member-button-primary mt-4">{{ $en?'Send message':'إرسال الرسالة' }} ←</button><x-human-check :action="route('consultation.reply')" /></form>@else<div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800">{{ $en?'This request has ended. You can still read the conversation.':'انتهى الطلب، ويمكنك قراءة المحادثة السابقة.' }}<a href="{{ route('consultation.track',['panel'=>'finish']) }}" class="ms-2 font-bold text-brand">{{ $en?'Rate service':'تقييم الخدمة' }} ←</a></div>@endif</section>
    @elseif($panel==='files')
        @if($case->evidenceRequests()->exists()) @include('consultation.secure-evidence') @else<div class="tracking-panel py-12 text-center"><h3 class="font-bold">{{ $en?'No private submissions requested':'لم يطلب الفريق مرفقات حاليًا' }}</h3><p class="mt-3 text-sm text-slate-500">{{ $en?'A private form will appear here if the specialist needs additional information.':'سيظهر نموذج خاص هنا عندما يحتاج المختص معلومات إضافية.' }}</p></div>@endif
    @elseif($panel==='plan' || $panel==='timeline')
        @include('support.request-progress',['progressSection'=>$panel])
    @elseif($panel==='details')
        <section class="tracking-panel"><dl class="grid gap-6 sm:grid-cols-2"><div><dt class="text-xs text-slate-500">{{ $en?'Created':'تاريخ الطلب' }}</dt><dd class="mt-2 font-bold">{{ $case->created_at->format('Y-m-d H:i') }}</dd></div><div><dt class="text-xs text-slate-500">{{ $en?'Status':'الحالة' }}</dt><dd class="mt-2 font-bold">{{ $case->status_label }}</dd></div></dl><h3 class="mt-7 border-t pt-5 font-bold">{{ $en?'Your submitted description':'تفاصيل الطلب الذي أرسلته' }}</h3><p class="mt-4 whitespace-pre-line text-sm leading-8">{{ $case->description }}</p></section>
    @elseif($panel==='access')
        <section class="tracking-panel"><h3 class="font-bold">{{ $en?'Return with one click':'العودة إلى الطلب بضغطة واحدة' }}</h3><p class="mt-3 text-sm leading-7">{{ $en?'Save your private link. It opens this conversation from another device for 30 days. Keep it private.':'احفظ رابطك الخاص لفتح المحادثة من أي جهاز خلال 30 يومًا. احتفظ به لنفسك.' }}</p><button type="button" data-copy="{{ $accessLink }}" class="member-button member-button-primary mt-5">{{ $en?'Copy private tracking link':'نسخ رابط المتابعة الخاص' }}</button></section>
        @include('support.request-progress',['progressSection'=>'access'])
        <section class="tracking-panel"><a href="{{ route('privacy.center') }}" class="font-bold text-brand">{{ __('workflow.privacy') }} ←</a><a href="{{ route('consultation.recovery') }}" class="mt-4 block text-sm text-brand">{{ __('workflow.recover') }} ←</a><form method="post" action="{{ route('consultation.forget') }}" class="mt-6 border-t pt-5">@csrf<button class="text-sm font-bold text-danger">{{ $en?'Hide this request from this session':'إخفاء الطلب من هذه الجلسة' }}</button></form></section>
    @elseif($panel==='finish')
        @include('consultation.closure-controls')
    @endif
    </div>
</div>
@else
<div class="mx-auto max-w-2xl space-y-5"><section class="tracking-panel"><h2 class="text-xl font-bold">{{ $en?'Open a previous request':'الوصول إلى طلب سابق' }}</h2><p class="mt-4 text-sm leading-8">{{ $en?'Open your saved private link or choose a request from your account.':'افتح رابط المتابعة الذي حفظته، أو اختر طلبًا من حسابك.' }}</p>@guest<a href="{{ route('login') }}" class="member-button member-button-primary mt-5">{{ $en?'Sign in and view my requests':'تسجيل الدخول وعرض طلباتي' }}</a>@endguest</section>
@if($memberRequests->isNotEmpty())<section class="tracking-panel"><h2 class="font-bold">{{ $en?'My requests':'طلبات حسابي' }}</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($memberRequests as $memberRequest)<a href="{{ route('account.requests.show',$memberRequest) }}" class="rounded-xl border p-4"><b>{{ $en?'Request':'طلب' }} {{ $memberRequest->request_number }}</b><p class="mt-2 text-sm">{{ $memberRequest->status_label }} ←</p></a>@endforeach</div></section>@endif
@if($searched)<p role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">{{ $en?'No request was found for this code.':'لم نعثر على طلب بهذا الرمز. تحقق منه وحاول مجددًا.' }}</p>@endif
<details class="tracking-panel" @if($errors->has('tracking_code')) open @endif><summary class="cursor-pointer font-bold">{{ $en?'I have a backup access code':'لدي رمز دخول احتياطي' }}</summary><p class="mt-4 text-sm leading-7">{{ $en?'Paste your 12-character private code, not the numeric request number.':'ألصق الرمز السري المكون من 12 حرفًا ورقمًا، وليس رقم الطلب الرقمي.' }}</p><form method="post" action="{{ route('consultation.track.lookup') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">@csrf<input aria-label="{{ $en?'Private access code':'رمز الدخول السري' }}" name="tracking_code" value="{{ old('tracking_code') }}" required class="member-input min-w-0 flex-1" autocomplete="off"><button class="member-button member-button-primary">{{ $en?'Open request':'فتح الطلب' }}</button><x-human-check :action="route('consultation.track.lookup')" /></form>@error('tracking_code')<p role="alert" class="mt-3 text-danger">{{ $message }}</p>@enderror</details><a href="{{ route('consultation.recovery') }}" class="block text-center text-sm font-bold text-brand">{{ __('workflow.recover') }} ←</a></div>
@endif
<p id="copy-status" role="status" class="mt-5 text-center text-sm text-brand"></p>
</section>
@include('consultation.copy-script')
@endsection
