<?php
namespace App\Policies;
use App\Models\{User,ReportingAuthority};
class ReportingAuthorityPolicy extends CountryPolicy
{
    public function view(User $user,\Illuminate\Database\Eloquent\Model $record): bool { return $this->viewAny($user); }
    public function update(User $user,\Illuminate\Database\Eloquent\Model $record): bool { return $this->viewAny($user); }
    public function delete(User $user,\Illuminate\Database\Eloquent\Model $record): bool { return $this->viewAny($user); }
}
