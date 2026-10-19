<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    private const SYSTEM_ROLES = ['student', 'representative', 'teacher', 'coordinator', 'admin'];

    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', User::class);
        $user = request()->user();
        $query = User::query();
        if ($user->system_role === 'admin') {
            return UserResource::collection($query->paginate($request->perPage()));
        }
        if ($user->system_role === 'teacher') {
            $classIds = $user->schoolClasses()
                ->wherePivot('role', 'teacher')
                ->select('school_classes.id');
            $query->where(function ($users) use ($user, $classIds) {
                $users->whereKey($user->id)
                    ->orWhereHas('schoolClasses', fn ($classes) => $classes->whereIn('school_classes.id', $classIds));
            });
        } else {
            $query->whereKey($user->id);
        }
        return UserResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        $validated = $request->validated();
        $validated['password'] = Hash::make(Str::random(48));
        $user = User::create($validated);
        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(string $id)
    {
        $user = User::find($id);
        abort_unless($user, 404, 'Usuario nao encontrado.');
        $this->authorize('view', $user);
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        $user = User::find($id);
        abort_unless($user, 404, 'Usuario nao encontrado.');
        $this->authorize('update', $user);
        $validated = $request->validated();
        $user->update($validated);
        return new UserResource($user->fresh());
    }

    public function destroy(string $id)
    {
        $user = User::find($id);
        abort_unless($user, 404, 'Usuario nao encontrado.');
        $this->authorize('delete', $user);
        try {
            $user->delete();
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23503') {
                return response()->json(['message' => 'Usuario possui registros vinculados.'], 409);
            }
            throw $exception;
        }
        return response()->json(null, 204);
    }
}
