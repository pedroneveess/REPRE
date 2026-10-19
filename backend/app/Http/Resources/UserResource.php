<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'system_role' => $this->system_role,
            'birthdate' => $this->birthdate?->toDateString(),
            'role' => $this->when(isset($this->pivot), fn () => $this->pivot->role),
        ];
    }
}
