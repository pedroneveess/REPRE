<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'birthdate' => ['sometimes', 'required', 'date'],
            'system_role' => ['sometimes', 'required', 'string', Rule::in(['student', 'representative', 'teacher', 'coordinator', 'admin'])],
        ];
    }
}
