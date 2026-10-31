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
<section class="container-page py-10" id="platform-statistics" aria-labelledby="statistics-title">
    <div class="stats-panel">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="text-sm font-bold text-blue-200">{{ $en?'The platform in numbers':'المنصة بالأرقام' }}</span>
                <h2 id="statistics-title" class="mt-2 text-2xl font-extrabold md:text-3xl">{{ $en?'Platform statistics':'إحصائيات المنصة' }}</h2>
            </div>
            <div class="stats-top"><h3>{{ $en?'Most frequently received help category':'أكثر فئات المساعدة ورودًا' }}</h3><p>{{ $labels[$statistics['top_category']] ?? ($en?'No requests yet':'لا توجد طلبات حتى الآن') }}</p></div>
        </div>
        <dl class="stats-grid">
            @foreach($metrics as [$label,$value])<div class="stats-item"><dt>{{ $label }}</dt><dd dir="ltr">{{ $value }}</dd></div>@endforeach
        </dl>
        <details class="mt-5 text-xs leading-7 text-slate-300"><summary class="cursor-pointer font-bold">{{ $en?'About these figures':'عن هذه الأرقام' }}</summary><p class="mt-2">{{ $en?'Visitors are approximate browser sessions counted since activation':'الزوار عدد تقريبي لجلسات التصفح منذ تفعيل العدّاد' }}@if($statistics['visits']) ({{ \Carbon\Carbon::parse($statistics['visits']->started_at)->format('Y-m-d') }})@endif. {{ $en?'Refreshing a page in the same session does not add another visitor. Known bots are excluded.':'تحديث الصفحة في الجلسة نفسها لا يضيف زائرًا جديدًا، وتُستبعد الروبوتات المعروفة.' }}</p>
        @if($statistics['legacy'])<p class="mt-2">{{ $en?'The total includes older requests whose service type was not recorded:':'يشمل الإجمالي طلبات قديمة لم يُسجّل نوع خدمتها:' }} {{ number_format($statistics['legacy']) }}. {{ $en?'Consultation and urgent support counts cover classified requests only.':'أعداد الاستشارات والمساعدة العاجلة تخص الطلبات المصنفة فقط.' }}</p>@endif
        </details>
    </div>
</section>
