<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassPerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tasks_total' => $this->tasks_total,
            'completions_total' => $this->completions_total,
            'pending_total' => max(0, $this->tasks_total - $this->completions_total),
            'percentage' => $this->tasks_total > 0 ? round(($this->completions_total / $this->tasks_total) * 100, 2) : 0,
        ];
    }
}
