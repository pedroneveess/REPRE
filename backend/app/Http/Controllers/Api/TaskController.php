<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\SchoolClass;
use App\Models\User;
use App\Policies\TaskPolicy;

class TaskController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Task::class);

        $user = auth()->user();
        $query = Task::with('schoolClass', 'creator', 'completions.user');

        if (!in_array($user->system_role, ['coordinator', 'admin'])) {
            $query->whereHas('schoolClass.members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
        $tasks = $query->get();

        return response()->json([
            'tasks' => $tasks
        ], 200);
    }

    public function show(string $id)
    {
        $task = Task::with('schoolClass', 'creator', 'completions.user')->find($id);

        if (!$task) {
            return response()->json([
                'message' => 'Tarefa n�o encontrada.'
            ], 404);
        }

        $this->authorize('view', $task);

        return response()->json([
            'task' => $task
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'nullable'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'due_date' => ['required', 'date'],
            'class_id' => ['required', 'uuid', 'exists:school_classes,id'],
        ]);

        $validated['created_by'] = $request->user()->id;

        if ($request->user()->system_role === 'teacher') {
            $isMember = SchoolClass::find($validated['class_id'])
                ->members()
                ->where('users.id', $request->user()->id)
                ->exists();

            if (!$isMember) {
                return response()->json([
                    'message' => 'Você não pode criar tarefas nesta turma.'
                ], 403);
            }
        }

        $task = Task::create($validated);

        return response()->json([
            'message' => 'Tarefa criada com sucesso.',
            'task' => $task
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'Tarefa n�o encontrada.'
            ], 404);
        }

        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'nullable'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'due_date' => ['sometimes', 'date'],
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Tarefa atualizada com sucesso.',
            'task' => $task->fresh()
        ], 200);
    }

    public function destroy(string $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'Tarefa n�o encontrada.'
            ], 404);
        }

        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(null, 204);
    }
}
