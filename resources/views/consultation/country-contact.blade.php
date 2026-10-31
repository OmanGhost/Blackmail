@php
    $english = app()->getLocale() === 'en';
@endphp
<div data-country-contact>
    <label for="consultation-country" class="font-bold">{{ $english ? 'Country' : 'الدولة' }}</label>
    <select id="consultation-country" name="country_id" class="mt-2 w-full rounded-xl border p-3" aria-controls="country-contact-notice">
        <option value="">{{ $english ? 'Not specified' : 'غير محدد' }}</option>
        @foreach($countries as $country)
            <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>{{ $english ? $country->name_en : $country->name_ar }}</option>
        @endforeach
    </select>
    <div id="country-contact-notice" aria-live="polite" aria-atomic="true">
        @foreach($countries->where('is_published', true)->where('verification_status', 'verified') as $country)
            @php
                $emergencyInfo = $english ? ($country->emergency_information_en ?: $country->emergency_information_ar) : $country->emergency_information_ar;
                $reportingInfo = $english ? ($country->reporting_instructions_en ?: $country->reporting_instructions_ar) : $country->reporting_instructions_ar;
            @endphp
            <aside class="country-contact-notice" data-country-notice="{{ $country->id }}" @if((string) old('country_id') !== (string) $country->id) hidden @endif>
                <h3 class="font-extrabold">{{ $english ? 'Verified reporting contacts' : 'جهات التواصل والإبلاغ المتحققة' }} — {{ $english ? $country->name_en : $country->name_ar }}</h3>
                @if($country->last_verified_at)<p class="mt-1 text-xs">{{ $english ? 'Last verified:' : 'آخر تحقق:' }} {{ $country->last_verified_at->format('Y-m-d') }}</p>@endif
                @if($emergencyInfo)<p class="mt-3 whitespace-pre-line">{{ $emergencyInfo }}</p>@endif
                @foreach($country->reportingAuthorities as $authority)
                    <div class="mt-3 border-t border-current/15 pt-3">
                        <strong>{{ $english ? ($authority->name_en ?: $authority->name_ar) : $authority->name_ar }}</strong>
                        <div class="mt-2 flex flex-wrap gap-3">
                            @if($authority->phone)
                                <a class="font-bold underline" href="tel:{{ preg_replace('/[^0-9+*#,-]/', '', $authority->phone) }}"><span>{{ $english ? 'Call:' : 'اتصل:' }}</span> <bdi dir="ltr">{{ $authority->phone }}</bdi></a>
                            @endif
                            @if($authority->official_url && in_array(strtolower(parse_url($authority->official_url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true))
                                <a class="font-bold underline" href="{{ $authority->official_url }}" target="_blank" rel="noopener noreferrer">{{ $english ? 'Official website ↗' : 'الموقع الرسمي ↗' }}</a>
                            @endif
                        </div>
                        @if($authority->instructions_ar)<p class="mt-2 whitespace-pre-line">{{ $authority->instructions_ar }}</p>@endif
                    </div>
                @endforeach
                @if($reportingInfo)<p class="mt-3 whitespace-pre-line">{{ $reportingInfo }}</p>@endif
                <a class="mt-3 inline-block font-bold underline" href="{{ route('countries.show', $country) }}" target="_blank" rel="noopener noreferrer">{{ $english ? 'Country details and reporting guidance ↗' : 'تفاصيل الدولة وإرشادات الإبلاغ ↗' }}</a>
                <p class="mt-2 text-xs">{{ $english ? 'You can contact the authority directly and continue completing this request.' : 'يمكنك التواصل مع الجهة مباشرة، ومتابعة تعبئة الطلب هنا.' }}</p>
            </aside>
        @endforeach
    </div>
</div>
