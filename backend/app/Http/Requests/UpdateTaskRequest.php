<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'nullable'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'due_date' => ['sometimes', 'date'],
        ];
    }
}
