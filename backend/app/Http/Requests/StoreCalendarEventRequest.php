<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:200'], 'description' => ['required', 'string'], 'starts_at' => ['required', 'date'], 'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'], 'class_id' => ['sometimes', 'nullable', 'uuid', 'exists:school_classes,id']];
    }
}
