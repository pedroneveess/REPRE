<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskCompletionRequest;
use App\Http\Requests\UpdateTaskCompletionRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\TaskCompletionResource;
use App\Models\TaskCompletion;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskCompletionPolicy;

class TaskCompletionController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', TaskCompletion::class);

        $user = auth()->user();

        $query = TaskCompletion::with('user', 'task');

        if ($user->system_role === 'admin') {
            return TaskCompletionResource::collection($query->paginate($request->perPage()));
        }
        if ($user->system_role === 'teacher') {
            $query->whereHas('task.schoolClass.members', fn ($q) => $q->whereKey($user->id)->wherePivot('role', 'teacher'));
        } else {
            $query->where('user_id', $user->id);
        }
        return TaskCompletionResource::collection($query->paginate($request->perPage()));
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

        return new TaskCompletionResource($completion);
    }

    public function store(StoreTaskCompletionRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        $task = Task::with('schoolClass')->findOrFail($validated['task_id']);
        $this->authorize('create', [TaskCompletion::class, $task]);
        abort_unless($user->system_role === 'admin' || $task->schoolClass->members()->whereKey($user->id)->exists(), 403);
        $completion = TaskCompletion::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'completed_at' => now(),
        ]);
        return (new TaskCompletionResource($completion))->response()->setStatusCode(201);
    }
    public function update(UpdateTaskCompletionRequest $request, string $id)
    {
        $completion = TaskCompletion::find($id);

        if (!$completion) {
            return response()->json([
                'message' => 'Conclus�o n�o encontrada.'
            ], 404);
        }

        $this->authorize('update', $completion);

        $validated = $request->validated();

        $completion->update($validated);

        return new TaskCompletionResource($completion->fresh());
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
