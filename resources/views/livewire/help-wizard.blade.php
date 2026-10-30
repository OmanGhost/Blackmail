<div class="container-page py-16"><div class="mx-auto max-w-3xl">
<span class="eyebrow">خاص ولا يتطلب التسجيل</span>
<h1 class="section-title">مركز المساعدة الفورية</h1>
<p class="mt-4">لا نحفظ إجاباتك ولا نطلب ملفات خاصة أو كلمات مرور.</p>
<div class="mt-8 flex gap-2">@for($i=1;$i<=4;$i++)<span class="h-2 flex-1 rounded-full {{ $i <= $step ? 'bg-brand' : 'bg-slate-200' }}"></span>@endfor</div>
<p class="mt-2 text-sm">الخطوة {{ $step }} من 4</p>
<div class="card mt-6">
@if($step===1)
<h2 class="text-2xl font-bold">ما نوع التهديد؟</h2>
<p class="mt-2 text-sm">انقر على الخيار مرة ثانية لإلغاء تحديده.</p>
<div class="mt-6 grid gap-3 sm:grid-cols-2">
@foreach(['intimate'=>'صور أو مقاطع خاصة','financial'=>'ابتزاز مالي','account'=>'اختراق حساب','identity'=>'انتحال هوية','family'=>'تهديد الأسرة أو الأصدقاء','deepfake'=>'تزييف عميق','child'=>'استغلال متعلق بطفل','other'=>'تهديد آخر'] as $value=>$label)
<button type="button" wire:key="threat-{{ $value }}" wire:click="selectThreat('{{ $value }}')" class="flex items-center justify-between rounded-2xl border p-4 text-start transition {{ $threatType===$value ? 'border-brand bg-blue-50 ring-2 ring-brand/20 dark:bg-blue-950' : 'hover:border-brand/50' }}" aria-pressed="{{ $threatType===$value ? 'true' : 'false' }}"><span>{{ $label }}</span><span>{{ $threatType===$value ? '✓' : '' }}</span></button>
@endforeach
</div>
@error('threatType')<p class="mt-3 rounded-xl bg-red-50 p-3 text-danger">اختر نوع التهديد للمتابعة.</p>@enderror
@elseif($step===2)
<h2 class="text-2xl font-bold">اختر بلدك</h2>
<select wire:model.live="country" class="mt-6 w-full rounded-2xl border bg-white p-4 dark:bg-slate-900"><option value="">بدون اختيار</option>@foreach($countries as $code=>$name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select>
@error('country')<p class="mt-3 rounded-xl bg-red-50 p-3 text-danger">اختر بلدك للمتابعة.</p>@enderror
@elseif($step===3)
<h2 class="text-2xl font-bold">أسئلة سلامة قصيرة</h2>
<div class="mt-6 space-y-4">@foreach(['published'=>'هل نُشر المحتوى بالفعل؟','accountAccess'=>'هل لدى الشخص وصول إلى حسابك؟','physicalDanger'=>'هل يوجد خطر جسدي مباشر؟','minorInvolved'=>'هل المتأثر دون 18 عاماً؟'] as $field=>$label)<label class="flex cursor-pointer justify-between rounded-2xl border p-4"><span>{{ $label }}</span><input type="checkbox" wire:model.live="{{ $field }}" class="size-5"></label>@endforeach</div>
@else
<h2 class="text-2xl font-bold">خطواتك الآن</h2>
@if($physicalDanger)<div class="mt-5 rounded-2xl bg-red-50 p-5 text-red-900"><b>السلامة الجسدية أولاً</b><p>ابتعد إلى مكان آمن واتصل بخدمات الطوارئ المحلية.</p></div>@endif
@if($minorInvolved||$threatType==='child')<div class="mt-5 rounded-2xl bg-amber-50 p-5 text-amber-950"><b>مسار حماية طفل</b><p>لا تلُم الطفل واستعن بولي أمر آمن وجهة حماية رسمية.</p></div>@endif
<div class="mt-6 space-y-3">@foreach(['توقف عن الدفع أو تنفيذ المطالب.','احفظ الرسائل والروابط والتواريخ.','أمّن حساباتك وفعّل المصادقة المتعددة.','أبلغ الجهة الرسمية المتحققة في بلدك.','شارك الموقف مع شخص تثق به.'] as $tip)<p class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900">✓ {{ $tip }}</p>@endforeach</div>
@endif
<div class="mt-8 flex justify-between">
@if($step>1)<button type="button" wire:click="back" wire:loading.attr="disabled" class="rounded-xl border px-5 py-3 font-bold">السابق</button>@else<span></span>@endif
@if($step<4)<button type="button" wire:click="next" wire:loading.attr="disabled" class="rounded-xl bg-brand px-6 py-3 font-bold text-white disabled:opacity-50"><span wire:loading.remove wire:target="next">التالي</span><span wire:loading wire:target="next">جارٍ المتابعة…</span></button>@else<a href="{{ route('home') }}" class="rounded-xl bg-navy px-6 py-3 font-bold text-white">العودة للرئيسية</a>@endif
</div></div></div></div>
