<?php

namespace App\Livewire;

use Livewire\Component;

class HelpWizard extends Component
{
    /** @var array<string, string> */
    public array $countries = [
        'OM' => 'عُمان', 'SA' => 'السعودية', 'AE' => 'الإمارات', 'BH' => 'البحرين', 'KW' => 'الكويت', 'QA' => 'قطر', 'YE' => 'اليمن',
        'IQ' => 'العراق', 'JO' => 'الأردن', 'PS' => 'فلسطين', 'LB' => 'لبنان', 'SY' => 'سوريا', 'EG' => 'مصر', 'SD' => 'السودان',
        'SO' => 'الصومال', 'DJ' => 'جيبوتي', 'KM' => 'جزر القمر', 'LY' => 'ليبيا', 'TN' => 'تونس', 'DZ' => 'الجزائر', 'MA' => 'المغرب', 'MR' => 'موريتانيا',
    ];

    public int $step = 1;

    public string $threatType = '';

    public string $country = '';

    public bool $published = false;

    public bool $accountAccess = false;

    public bool $physicalDanger = false;

    public bool $minorInvolved = false;

    public function next(): void
    {
        if ($this->step === 1) {
            $this->validateOnly('threatType', ['threatType' => 'required|in:intimate,financial,account,identity,family,deepfake,child,other']);
        }
        if ($this->step === 2) {
            $this->validateOnly('country', ['country' => 'required|in:'.implode(',', array_keys($this->countries))]);
        }
        $this->step = min(4, $this->step + 1);
        $this->resetValidation();
    }

    public function selectThreat(string $value): void
    {
        abort_unless(in_array($value, ['intimate', 'financial', 'account', 'identity', 'family', 'deepfake', 'child', 'other'], true), 422);
        $this->threatType = $this->threatType === $value ? '' : $value;
        $this->resetValidation('threatType');
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function render()
    {
        return view('livewire.help-wizard')->layout('layouts.app', ['title' => __('site.help_title')]);
    }
}
