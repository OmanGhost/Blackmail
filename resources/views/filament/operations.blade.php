<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">@foreach($this->counts() as $key=>$value)<div class="admin-metric"><p>{{ __('workflow.operations_'.$key) }}</p><b dir="ltr">{{ $value }}</b></div>@endforeach</div>
    <p class="text-sm text-gray-500">{{ __('workflow.operations_description') }}</p>
    {{ $this->table }}
</x-filament-panels::page>
