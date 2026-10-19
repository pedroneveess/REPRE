<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'birthdate' => ['required', 'date'],
            'system_role' => ['required', 'string', Rule::in(['student', 'representative', 'teacher', 'coordinator', 'admin'])],
        ];
    }
}
