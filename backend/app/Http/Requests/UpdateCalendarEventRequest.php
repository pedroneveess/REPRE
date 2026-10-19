<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['title' => ['sometimes', 'string', 'max:200'], 'description' => ['sometimes', 'string'], 'starts_at' => ['sometimes', 'date'], 'ends_at' => ['sometimes', 'nullable', 'date'], 'class_id' => ['sometimes', 'nullable', 'uuid', 'exists:school_classes,id']];
    }
}
