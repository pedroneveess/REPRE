<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaskCompletion;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskCompletionPolicy;

class TaskCompletionController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', TaskCompletion::class);

        $user = auth()->user();

        $query = TaskCompletion::with('user', 'task');

        if (!in_array($user->system_role, ['coordinator', 'admin'])) {
            if ($user->system_role === 'teacher') {
                $query->whereHas('task.schoolClass.members', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        }
        $completions = $query->get();

        return response()->json([
            'task_completions' => $completions
        ], 200);
    }

    public function show(string $id)
    {
        $completion = TaskCompletion::with('user', 'task')->find($id);

        if (!$completion) {
            return response()->json([
                'message' => 'Conclus�o n�o encontrada.'
            ], 404);
        }

        $this->authorize('view', $completion);

        return response()->json([
            'task_completion' => $completion
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', TaskCompletion::class);

        $validated = $request->validate([
            'task_id' => ['required', 'uuid', 'exists:tasks,id'],
        ]);

        $user = $request->user();

        $existing = TaskCompletion::where('user_id', $user->id)
            ->where('task_id', $validated['task_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Tarefa j� marcada como conclu�da.'
            ], 409);
        }

        $completion = TaskCompletion::create([
            'user_id' => $user->id,
            'task_id' => $validated['task_id'],
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tarefa marcada como conclu�da.',
            'task_completion' => $completion
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $completion = TaskCompletion::find($id);

        if (!$completion) {
            return response()->json([
                'message' => 'Conclus�o n�o encontrada.'
            ], 404);
        }

        $this->authorize('update', $completion);

        $validated = $request->validate([
            'completed_at' => ['sometimes', 'date'],
        ]);

        $completion->update($validated);

        return response()->json([
            'message' => 'Conclus�o atualizada com sucesso.',
            'task_completion' => $completion->fresh()
        ], 200);
    }

    public function destroy(string $id)
    {
        $completion = TaskCompletion::find($id);

        if (!$completion) {
            return response()->json([
                'message' => 'Conclus�o n�o encontrada.'
            ], 404);
        }

        $this->authorize('delete', $completion);

        $completion->delete();

        return response()->json(null, 204);
    }
}
