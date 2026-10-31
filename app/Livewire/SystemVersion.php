<?php
namespace App\Livewire;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\ApplicationUpdates;
class SystemVersion extends Component
{
    #[\Livewire\Attributes\Locked]
    public ?array $result=null;
    private function authorized(): bool { $user=Auth::guard('admin')->user();return $user?->is_active && $user->hasRole('super-admin'); }
    public function mount(): void { if ($this->authorized()) $this->result=app(ApplicationUpdates::class)->cached(); }
    public function check(): void
    {
        abort_unless($this->authorized(),403);
        $this->result=app(ApplicationUpdates::class)->check();
    }
    public function render()
    {
        $service=app(ApplicationUpdates::class);
        return view('livewire.system-version',['version'=>$service->version(),'status'=>$service->displayStatus($this->result),'canCheck'=>$this->authorized()]);
    }
}
