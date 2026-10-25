<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\SchoolClass;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\TaskIndexRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(TaskIndexRequest $request)
    {
        $this->authorize('viewAny', Task::class);

        $user = auth()->user();
        $query = Task::with('schoolClass', 'creator');

        if (!in_array($user->system_role, ['coordinator', 'admin'])) {
            $query->whereHas('schoolClass.members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
        $filters = $request->validated();
        foreach (['class_id', 'priority'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (array_key_exists('completed', $filters)) {
            $query->whereHas('completions', fn ($q) => $q->where('user_id', $user->id), $request->boolean('completed') ? '>=' : '=', $request->boolean('completed') ? 1 : 0);
        }
        foreach (['due_from' => '>=', 'due_to' => '<='] as $field => $operator) {
            if (isset($filters[$field])) {
                $query->where('due_date', $operator, $request->deadline($field));
            }
        }
        if (isset($filters['search'])) {
            $query->where('title', 'ilike', '%'.addcslashes($filters['search'], '\\%_').'%');
        }
        $order = $filters['order'] ?? 'asc';
        return TaskResource::collection($query->orderBy('due_date', $order)->orderBy('id', $order)
            ->paginate($request->perPage())->withQueryString());
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
        $validated = $request->validated();
        $schoolClass = SchoolClass::findOrFail($validated['class_id']);
        $this->authorize('create', [Task::class, $schoolClass]);

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

        try {
            DB::transaction(function () use ($task) {
                $task->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23503') {
                return response()->json(['message' => 'Tarefa possui conclusoes vinculadas.'], 409);
            }
            throw $exception;
        }

        return response()->json(null, 204);
    }
}
