<?php

namespace App\Services;

use App\Models\User;

/**
 * Service para cosas del usuario. Lo instancio con app()->get(UserService::class).
 */
class UserService
{
    /**
     * Activa el rol de organizador (cualquier usuario puede activarselo).
     */
    public function activarOrganizador(User $user): User
    {
        $user->update(['is_organizer' => true]);

        return $user;
    }
}
