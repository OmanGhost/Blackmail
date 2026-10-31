@php
    $en = app()->getLocale() === 'en';
    $labels = $en ? ['blackmail'=>'Online blackmail','account'=>'Account compromise','privacy'=>'Digital privacy','evidence'=>'Evidence preservation','other'=>'Other help'] : ['blackmail'=>'ابتزاز إلكتروني','account'=>'اختراق حساب','privacy'=>'خصوصية رقمية','evidence'=>'حفظ الأدلة','other'=>'مساعدة أخرى'];
    $metrics = [
        [$en?'Requests received':'إجمالي الطلبات المستلمة', number_format($statistics['total'])],
        [$en?'Consultation requests':'طلبات الاستشارة', number_format($statistics['consultations'])],
        [$en?'Urgent support requests':'طلبات المساعدة العاجلة', number_format($statistics['support'])],
        [$en?'Completed requests':'طلبات تم الانتهاء منها', number_format($statistics['completed'])],
        [$en?'Verified countries':'دول متحقق منها', number_format($countries->count())],
        [$en?'Platform visitors (approx.)':'زوار المنصة (تقريبي)', number_format($statistics['visits']?->visitor_sessions ?? 0)],
    ];
@endphp
<section class="container-page py-8" id="platform-statistics" aria-labelledby="statistics-title">
    <div class="rounded-[2rem] bg-navy p-5 text-white md:p-6">
        <span class="text-sm font-bold text-blue-200">{{ $en?'The platform in numbers':'المنصة بالأرقام' }}</span>
        <h2 id="statistics-title" class="mt-2 text-2xl font-extrabold">{{ $en?'Platform statistics':'إحصائيات المنصة' }}</h2>
        <dl class="statistics-circles">
            @foreach($metrics as [$label,$value])<div class="statistics-item"><dt class="text-xs leading-6 text-slate-200">{{ $label }}</dt><dd class="statistics-circle" dir="ltr">{{ $value }}</dd></div>@endforeach
        </dl>
        <div class="flex flex-wrap items-center gap-3 border-t border-white/15 pt-3"><h3 class="text-sm text-blue-100">{{ $en?'Most frequently received help category':'أكثر فئات المساعدة ورودًا' }}</h3><p class="text-sm font-bold">{{ $labels[$statistics['top_category']] ?? ($en?'No requests yet':'لا توجد طلبات حتى الآن') }}</p></div>
        <details class="mt-3 text-xs leading-7 text-slate-300"><summary class="cursor-pointer">{{ $en?'About these figures':'عن هذه الأرقام' }}</summary><p>{{ $en?'Visitors are approximate browser sessions counted since activation':'الزوار عدد تقريبي لجلسات التصفح منذ تفعيل العدّاد' }}@if($statistics['visits']) ({{ \Carbon\Carbon::parse($statistics['visits']->started_at)->format('Y-m-d') }})@endif. {{ $en?'Refreshing a page in the same session does not add another visitor. Known bots are excluded.':'تحديث الصفحة في الجلسة نفسها لا يضيف زائرًا جديدًا، وتُستبعد الروبوتات المعروفة.' }}</p>
        @if($statistics['legacy'])<p class="mt-2 text-xs leading-7 text-slate-300">{{ $en?'The total includes older requests whose service type was not recorded:':'يشمل الإجمالي طلبات قديمة لم يُسجّل نوع خدمتها:' }} {{ number_format($statistics['legacy']) }}. {{ $en?'Consultation and urgent support counts cover classified requests only.':'أعداد الاستشارات والمساعدة العاجلة تخص الطلبات المصنفة فقط.' }}</p>@endif
        </details>
    </div>
</section>
