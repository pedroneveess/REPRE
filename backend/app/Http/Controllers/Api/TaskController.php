<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\SchoolClass;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\TaskResource;

class TaskController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', Task::class);

        $user = auth()->user();
        $query = Task::with('schoolClass', 'creator');

        if (!in_array($user->system_role, ['coordinator', 'admin'])) {
            $query->whereHas('schoolClass.members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
        return TaskResource::collection($query->paginate($request->perPage()));
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

        return new TaskResource($task);
    }

    public function store(StoreTaskRequest $request)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validated();

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

        return (new TaskResource($task))->response()->setStatusCode(201);
    }

    public function update(UpdateTaskRequest $request, string $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'Tarefa n�o encontrada.'
            ], 404);
        }

        $this->authorize('update', $task);

        $validated = $request->validated();

        $task->update($validated);

        return new TaskResource($task->fresh());
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
