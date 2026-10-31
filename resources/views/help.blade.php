@extends('layouts.app',['title'=>app()->getLocale()==='en'?'Help guide':'دليل المساعدة خطوة بخطوة'])
@section('content')
@php
$en=app()->getLocale()==='en';
$steps=$en?['Threat type','Country','Safety','Your next steps']:['نوع التهديد','الدولة وجهات الإبلاغ','السلامة','خطواتك التالية'];
$threats=$en?['intimate'=>'Private images or videos','financial'=>'Financial blackmail','account'=>'Account compromise','identity'=>'Impersonation','family'=>'Threats to family or friends','deepfake'=>'Deepfake','child'=>'Child exploitation','other'=>'Other threat']:['intimate'=>'صور أو مقاطع خاصة','financial'=>'ابتزاز مالي','account'=>'اختراق حساب','identity'=>'انتحال هوية','family'=>'تهديد الأسرة أو الأصدقاء','deepfake'=>'تزييف عميق','child'=>'استغلال متعلق بطفل','other'=>'تهديد آخر'];
$questions=$en?['published'=>'Content has already been published','account'=>'The person can access my account','danger'=>'There is immediate physical danger','minor'=>'The affected person is under 18']:['published'=>'نُشر المحتوى بالفعل','account'=>'لدى الشخص وصول إلى حسابي','danger'=>'يوجد خطر جسدي مباشر','minor'=>'المتأثر دون 18 عامًا'];
$advice=$en?[
'intimate'=>'Do not send more private images or videos. Keep account links, messages and dates without redistributing private content.',
'financial'=>'Stop paying or complying with demands. Keep demand messages, dates and available transfer details.',
'account'=>'Use a safe device to change your password, review sessions and recovery methods, and enable multifactor authentication.',
'identity'=>'Keep the impersonating account link, report it through the platform tools, and alert trusted people not to respond.',
'family'=>'Tell someone you trust and choose a safe contact method. Keep threats directed at you or your family.',
'deepfake'=>'Keep links, account details and dates. Report the fabricated content without redistributing it.',
'child'=>'Prioritize the child’s safety. Involve a safe adult and an official protection service without blame or requesting private content.',
'other'=>'Keep a brief description, threat messages, account links and dates. Choose a safe way to contact the team.'
]:[
'intimate'=>'لا ترسل المزيد من الصور أو المقاطع الخاصة. احتفظ بروابط الحسابات والرسائل والتواريخ دون إعادة نشر المحتوى الخاص.',
'financial'=>'توقف عن الدفع أو تنفيذ المطالب، واحتفظ برسائل المطالبة وتواريخها وبيانات التحويل المتاحة.',
'account'=>'أمّن الحساب من جهاز آمن: غيّر كلمة المرور، راجع الجلسات ووسائل الاسترداد، وفعّل المصادقة المتعددة.',
'identity'=>'احتفظ برابط الحساب المنتحل وبلّغ عنه عبر أدوات المنصة، ونبّه من تثق بهم إلى عدم الاستجابة له.',
'family'=>'شارك الموقف مع شخص موثوق واختر وسيلة تواصل آمنة. احتفظ برسائل التهديد الموجهة لك أو لأقاربك.',
'deepfake'=>'احتفظ بالروابط وبيانات الحسابات والتواريخ، وبلّغ عن المحتوى المزيف دون المساهمة في إعادة نشره.',
'child'=>'الأولوية لحماية الطفل والتواصل مع شخص بالغ آمن وجهة حماية رسمية، دون لوم أو طلب محتوى خاص منه.',
'other'=>'احتفظ بوصف موجز للتهديد ورسائله وروابط الحسابات وتواريخه، واختر وسيلة آمنة للتواصل مع الفريق.'
];
@endphp
<div class="container-page py-12" id="help-wizard">
<span class="eyebrow">{{ $en?'Instant guidance • No registration':'إرشاد فوري • دون تسجيل' }}</span>
<h1 class="section-title">{{ $en?'Step-by-step help guide':'دليل المساعدة خطوة بخطوة' }}</h1>
<p class="mt-4 max-w-3xl leading-8">{{ $en?'Answer a few short questions for initial steps suited to your situation. Takes about a minute.':'أجب عن أسئلة قصيرة لتحصل على خطوات أولية تناسب حالتك وقنوات الإبلاغ المتاحة في بلدك. يستغرق نحو دقيقة.' }}</p>
<details class="card mt-6"><summary class="cursor-pointer font-bold">{{ $en?'How does the guide work?':'كيف يعمل الدليل؟' }}</summary><ol class="mt-4 list-inside list-decimal space-y-3">
<li>{{ $en?'Choose the closest threat type. You can change or deselect it.':'اختر نوع التهديد الأقرب لحالتك؛ يمكنك تغيير الاختيار أو إلغاؤه.' }}</li>
<li>{{ $en?'Optionally choose a country to see published, verified reporting contacts.':'اختر الدولة لعرض جهات التواصل المنشورة والمتحقق منها، أو تابع دون اختيار.' }}</li>
<li>{{ $en?'Select the safety circumstances that apply, then view your guidance.':'حدد ظروف السلامة التي تنطبق عليك، ثم اعرض خطواتك التالية.' }}</li>
<li>{{ $en?'Answers stay in page memory and clear on reload. They are not sent to the team or saved to your account. This guide does not create a support request.':'إجاباتك تبقى في ذاكرة الصفحة وتُمسح عند تحديثها. لا تُرسل للفريق ولا تُحفظ في حسابك. الدليل لا ينشئ طلب مساعدة تلقائيًا.' }}</li>
</ol></details>
<noscript><p class="card mt-5">{{ $en?'Enable JavaScript to use the guide, or open the support form.':'فعّل JavaScript لاستخدام الدليل، أو افتح نموذج المساعدة.' }} <a href="{{ route('blackmail.help') }}" class="text-brand underline">{{ $en?'Request support':'طلب مساعدة' }}</a></p></noscript>
<div class="help-layout mt-8">
<aside class="space-y-5"><nav class="card" aria-label="{{ $en?'Guide steps':'خطوات الدليل' }}"><ol class="space-y-4">@foreach($steps as $name)<li data-progress="{{ $loop->iteration }}" @if($loop->first) aria-current="step" @endif><span class="help-step-number">{{ $loop->iteration }}</span> {{ $name }}</li>@endforeach</ol></nav>
<div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-950"><h2 class="font-bold">{{ $en?'Physical safety first':'السلامة الجسدية أولاً' }}</h2><p class="mt-3 text-sm leading-7">{{ $en?'In immediate physical danger, move to safety and contact local emergency services now. Do not wait to finish this guide or for a team response.':'عند وجود خطر جسدي مباشر، انتقل إلى مكان آمن واتصل بالطوارئ المحلية فورًا. لا تنتظر إكمال الدليل أو رد الفريق.' }}</p><a href="{{ route('blackmail.help') }}" class="mt-4 inline-block font-bold underline">{{ $en?'Request support':'إرسال طلب مساعدة' }} ←</a></div></aside>
<div class="card help-panel">
<p id="step-label" class="mb-5 text-sm font-bold text-brand" aria-live="polite" data-format="{{ $en?'Step :step of 4':'الخطوة :step من 4' }}">{{ $en?'Step 1 of 4':'الخطوة 1 من 4' }}</p>
<section data-step="1"><h2 tabindex="-1" class="text-2xl font-bold">{{ $en?'Which threat best matches your situation?':'ما نوع التهديد الأقرب لحالتك؟' }}</h2><p class="mt-3 text-sm">{{ $en?'Choose one. Click again to deselect. If unsure, choose Other threat.':'اختر خيارًا واحدًا واضغط عليه مجددًا لإلغائه. إن لم تكن متأكدًا اختر «تهديد آخر».' }}</p><div class="mt-6 grid gap-3 sm:grid-cols-2">@foreach($threats as $value=>$label)<button type="button" data-threat="{{ $value }}" aria-pressed="false" class="help-choice"><span>{{ $label }}</span><span class="check" aria-hidden="true"></span></button>@endforeach</div><p data-error="threat" role="alert" class="mt-4 text-danger" hidden>{{ $en?'Choose a threat type to continue.':'اختر نوع التهديد للمتابعة.' }}</p></section>
<section data-step="2" hidden><h2 tabindex="-1" class="text-2xl font-bold">{{ $en?'Where do you need help?':'أين تحتاج إلى المساعدة؟' }}</h2><p class="my-4 text-sm leading-7">{{ $en?'Country is optional and used to display verified reporting contacts only.':'اختيار الدولة اختياري، ويستخدم لعرض قنوات الإبلاغ المتحقق منها فقط.' }}</p>@include('consultation.country-contact')<p class="mt-4 text-sm">{{ $en?'No contacts displayed? You can continue without a country or consult the country directory.':'لم تظهر بيانات اتصال؟ يمكنك المتابعة دون دولة أو مراجعة دليل الدول.' }} <a href="{{ route('countries') }}" target="_blank" rel="noopener noreferrer" class="text-brand underline">{{ $en?'Country directory ↗':'دليل الدول ↗' }}</a></p></section>
<section data-step="3" hidden><h2 tabindex="-1" class="text-2xl font-bold">{{ $en?'What applies to your situation?':'ما الذي ينطبق على حالتك؟' }}</h2><p class="mt-3 text-sm">{{ $en?'Select all that apply. Leave unchecked if none apply. You can undo any selection.':'حدد كل ما ينطبق. اترك الخيارات فارغة إذا لم ينطبق أي منها. يمكنك إلغاء أي تحديد.' }}</p><div class="mt-6 space-y-3">@foreach($questions as $key=>$label)<label class="help-choice"><span>{{ $label }}</span><input type="checkbox" data-safety="{{ $key }}" class="size-5"></label>@endforeach</div></section>
<section data-step="4" hidden><h2 tabindex="-1" class="text-2xl font-bold">{{ $en?'Your next steps':'خطواتك التالية' }}</h2><p class="mt-3 leading-7">{{ $en?'Initial guidance based on your choices, not a specialist assessment or confirmation that the blackmail has stopped.':'إرشادات أولية مبنية على اختياراتك، وليست تقييمًا من مختص أو تأكيدًا لإيقاف الابتزاز.' }}</p>
<p class="mt-4 rounded-xl bg-blue-50 p-4 text-blue-950"><b>{{ $en?'Threat type:':'نوع التهديد:' }}</b> <span data-summary></span></p>
<div data-guidance="danger" class="help-guidance help-guidance-danger" hidden><h3 class="font-bold">{{ $en?'Physical safety first':'السلامة الجسدية أولاً' }}</h3><p>{{ $en?'Move to safety and contact local emergency services immediately.':'ابتعد إلى مكان آمن واتصل بالطوارئ المحلية فورًا.' }}</p></div>
<div data-guidance="minor" class="help-guidance" hidden><h3 class="font-bold">{{ $en?'Child protection':'مسار حماية طفل' }}</h3><p>{{ $en?'Involve a safe adult and an official protection service. Do not blame the child or download or forward intimate content involving a child.':'استعن بشخص بالغ آمن وجهة حماية رسمية. لا تلُم الطفل ولا تنزّل أو تعِد إرسال المحتوى الحميمي المتعلق بطفل.' }}</p></div>
@foreach($advice as $key=>$text)<div data-advice="{{ $key }}" class="help-guidance" hidden><h3 class="font-bold">{{ $threats[$key] }}</h3><p>{{ $text }}</p></div>@endforeach
<p data-guidance="published" class="help-guidance" hidden>{{ $en?'For published content, keep the link and report it through the platform tools. Do not redistribute it when seeking help.':'إذا نُشر المحتوى، احتفظ بالرابط وبلّغ عنه عبر أدوات المنصة؛ لا تعِد نشره أثناء طلب المساعدة.' }}</p>
<p data-guidance="account" class="help-guidance" hidden>{{ $en?'Review account sessions, passwords and recovery methods from a safe device. Do not share verification codes.':'راجع جلسات الحساب وكلمة المرور ووسائل الاسترداد من جهاز آمن. لا تشارك رموز التحقق.' }}</p>
<h3 class="mt-6 font-bold">{{ $en?'Basic steps':'خطوات أساسية' }}</h3><ol class="mt-4 list-inside list-decimal space-y-3"><li>{{ $en?'Do not pay under pressure or send passwords or verification codes.':'لا تدفع تحت الضغط ولا ترسل كلمات مرور أو رموز تحقق.' }}</li><li>{{ $en?'Keep messages, account links and dates without redistributing private content.':'احتفظ بالرسائل وروابط الحسابات والتواريخ دون إعادة نشر المحتوى الخاص.' }}</li><li>{{ $en?'Involve someone you trust and use official reporting channels.':'استعن بشخص تثق به واستخدم قنوات الإبلاغ الرسمية.' }}</li></ol>
<div data-result-country class="mt-5"></div>
<div class="mt-6 rounded-2xl bg-navy p-5 text-white"><h3 class="font-bold">{{ $en?'Need a response from the team?':'تحتاج متابعة من الفريق؟' }}</h3><p class="mt-3 text-sm leading-7">{{ $en?'No request has been created. Open the form and submit a brief description and a safe contact method. You will receive a request number and tracking access after submission.':'لم يُنشأ أي طلب حتى الآن. افتح النموذج وأرسل وصفًا مختصرًا ووسيلة تواصل آمنة. بعد الإرسال تحصل على رقم طلب ووسيلة متابعة.' }}</p><a href="{{ route('blackmail.help') }}" class="mt-4 inline-flex rounded-xl bg-danger px-5 py-3 font-bold">{{ $en?'Submit a support request':'إرسال طلب مساعدة' }}</a><a href="{{ route('consultation') }}" class="ms-4 mt-4 inline-flex underline">{{ $en?'Request a consultation':'طلب استشارة' }}</a></div>
</section>
<div class="mt-8 flex flex-wrap items-center justify-between gap-3"><button type="button" id="previous" hidden class="rounded-xl border px-5 py-3 font-bold">{{ $en?'Previous':'السابق' }}</button><button type="button" id="next" class="rounded-xl bg-brand px-6 py-3 font-bold text-white" data-next="{{ $en?'Next':'التالي' }}" data-result="{{ $en?'Show my steps':'عرض خطواتي' }}" data-restart="{{ $en?'Clear answers and restart':'مسح الإجابات والبدء من جديد' }}">{{ $en?'Next':'التالي' }}</button></div>
</div></div></div>
@endsection
