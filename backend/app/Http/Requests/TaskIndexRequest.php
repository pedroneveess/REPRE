<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class TaskIndexRequest extends PaginationRequest
{
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('completed'), ['true', 'false'], true)) {
            $this->merge(['completed' => $this->input('completed') === 'true']);
        }
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'class_id' => ['sometimes', 'uuid'],
            'completed' => ['sometimes', 'boolean'],
            'due_from' => ['sometimes', 'date'],
            'due_to' => ['sometimes', 'date'],
            'search' => ['sometimes', 'string', 'max:100'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'order' => ['sometimes', 'string', 'in:asc,desc'],
        ]);
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (!$validator->errors()->any() && $this->has('due_from') && $this->has('due_to')
                && $this->deadline('due_to')->lt($this->deadline('due_from'))) {
                $validator->errors()->add('due_to', 'A data final deve ser igual ou posterior a inicial.');
            }
        }];
    }

    public function deadline(string $field): Carbon
    {
        $value = $this->input($field);
        $date = Carbon::parse($value, 'America/Sao_Paulo')->setTimezone('America/Sao_Paulo');
        return $field === 'due_to' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            ? $date->endOfDay() : $date;
    }
}
