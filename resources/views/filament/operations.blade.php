<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">@foreach($this->counts() as $key=>$value)<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900"><p class="text-sm text-gray-500">{{ __('workflow.operations_'.$key) }}</p><b class="mt-3 block text-3xl">{{ $value }}</b></div>@endforeach</div>
    <p class="text-sm text-gray-500">{{ __('workflow.operations_description') }}</p>
    {{ $this->table }}
</x-filament-panels::page>
