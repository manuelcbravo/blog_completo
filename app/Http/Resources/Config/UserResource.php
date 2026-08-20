<?php

namespace App\Http\Resources\Config;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'es_super_admin' => $this->es_super_admin,
            'created_at' => $this->created_at?->toISOString(),
            'roles' => $this->relationLoaded('roles')
                ? RoleResource::collection($this->roles->values())->resolve()
                : [],
        ];
    }
}
