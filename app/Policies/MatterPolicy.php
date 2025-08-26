<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\Matter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MatterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the matter.
     *
     * @return mixed
     */
    public function view(User $user, Matter $matter)
    {
        if(PermissionHelper::isClient($user)) {
            if($matter->client->count()) {
                return $matter->client->where('id', $user->id)->notEmpty();
            }

            return false;
        }

        return PermissionHelper::canReadOnly($user);
    }
}
