<?php
namespace App\Policies;
use App\Models\{User,Country};
class CountryPolicy
{
    public function viewAny(User $user): bool { return $user->is_active && $user->roles()->whereIn('name',['super-admin','legal-reviewer'])->exists(); }
    public function view(User $user,Country $record): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user,Country $record): bool { return $this->viewAny($user); }
    public function delete(User $user,Country $record): bool { return $this->viewAny($user); }
    public function deleteAny(User $user): bool { return $this->viewAny($user); }
}
