<?php

namespace App\Policies;

use App\Models\StoreSetting;
use App\Models\User;

class StoreSettingPolicy
{
    public function view(User $user, StoreSetting $settings): bool
    {
        return true;
    }

    public function update(User $user, StoreSetting $settings): bool
    {
        return $user->role === 'owner';
    }
}
