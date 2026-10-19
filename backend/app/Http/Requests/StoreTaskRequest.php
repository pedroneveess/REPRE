<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'nullable'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'due_date' => ['required', 'date'],
            'class_id' => ['required', 'uuid', 'exists:school_classes,id'],
        ];
    }
}
