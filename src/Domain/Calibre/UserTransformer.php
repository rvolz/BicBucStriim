<?php

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform($user)
    {
        return [
            'id' => (int)$user->id,
            'name' => $user->username,
            'role' => (int)$user->role,
            'email' => $user->email,
            'languages' => $user->languages,
            'tags' => $user->tags,
            'links' => [
                'rel' => 'self',
                'uri' => '/admin/users/' . $user->id,
            ],
        ];
    }
}
