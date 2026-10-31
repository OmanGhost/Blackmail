<footer class="system-version-footer" @if($canCheck) wire:init="check" @endif>
    <span>{{ config('app.name') }} · {{ __('updates.version') }} <b dir="ltr">v{{ $version }}</b></span>
    @if($canCheck)<span wire:loading.remove wire:target="check">{{ __('updates.'.$status) }}</span><span wire:loading wire:target="check">{{ __('updates.check') }}…</span><a href="{{ \App\Filament\Pages\Updates::getUrl() }}" class="font-bold underline">{{ __('updates.title') }}</a>@endif
</footer>
