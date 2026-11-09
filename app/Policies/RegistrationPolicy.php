<?php

namespace App\Policies;

use App\Enums\RegistrationType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RegistrationPolicy
{
    /**
     * Internal is only for verified Universitas Ciputra accounts; External and Bundling are open to everyone.
     */
    public function create(User $user, RegistrationType $type): Response
    {
        return $user->canRegisterAs($type)
            ? Response::allow()
            : Response::deny('Kategori Internal hanya untuk akun Google dengan email Universitas Ciputra (ciputra.ac.id).');
    }
}
