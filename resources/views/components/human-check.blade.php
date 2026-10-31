@props(['action'])
@if(app(\App\Services\HumanChallenge::class)->required(request()))
@php
    $challenge = app(\App\Services\HumanChallenge::class)->issue(request(), trim(parse_url($action, PHP_URL_PATH) ?? '/', '/'));
    $english = app()->getLocale() === 'en';
@endphp
<fieldset class="human-check">
    <legend class="font-bold">{{ $english ? 'Human verification' : 'التحقق من المرسل' }}</legend>
    <input type="hidden" name="human_token" value="{{ $challenge['token'] }}">
    <label class="flex flex-wrap items-center gap-3"><span>{{ $english ? 'What is' : 'ما ناتج' }} <bdi dir="ltr">{{ $challenge['question'] }}</bdi> {{ $english ? '?' : '؟' }}</span><input name="human_answer" inputmode="numeric" autocomplete="off" required maxlength="2" class="rounded-lg border bg-white p-2 text-slate-900" style="width:5rem" aria-label="{{ $english ? 'Answer to the verification question' : 'إجابة سؤال التحقق' }}"></label>
    <div class="human-trap" aria-hidden="true"><label>Website<input name="human_website" tabindex="-1" autocomplete="off"></label></div>
    <p class="mt-2 text-xs">{{ $english ? 'A simple local anti-bot check. Valid for 30 minutes, used once.' : 'تحقق محلي بسيط لتقليل الإرسال الآلي. صالح 30 دقيقة ولمرة واحدة.' }}</p>
    @error('human_answer')<p role="alert" class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
</fieldset>
@endif
