<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const SYSTEM_ROLES = ['student', 'representative', 'teacher', 'coordinator', 'admin'];

    public function index()
    {
        $this->authorize('viewAny', User::class);
        $user = request()->user();
        $query = User::query();
        if (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->where(function ($users) use ($user) {
                $users->whereKey($user->id)
                    ->orWhereHas('schoolClasses.members', fn ($members) => $members->where('users.id', $user->id))
                    ->orWhereHas('organizations.members', fn ($members) => $members->where('users.id', $user->id));
            });
        }
        return response()->json(['users' => $query->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'birthdate' => ['required', 'date'],
            'system_role' => ['required', 'string', Rule::in(self::SYSTEM_ROLES)],
        ]);
        $validated['password'] = Hash::make(Str::random(48));
        $user = User::create($validated);
        return response()->json(['user' => $user], 201);
    }

    public function show(string $id)
    {
        $user = User::find($id);
        abort_unless($user, 404, 'Usuario nao encontrado.');
        $this->authorize('view', $user);
        return response()->json(['user' => $user]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        abort_unless($user, 404, 'Usuario nao encontrado.');
        $this->authorize('update', $user);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birthdate' => ['sometimes', 'required', 'date'],
            'system_role' => ['sometimes', 'required', 'string', Rule::in(self::SYSTEM_ROLES)],
        ]);
        $user->update($validated);
        return response()->json(['user' => $user->fresh()]);
    }

    public function destroy(string $id)
    {
        $user = User::find($id);
        abort_unless($user, 404, 'Usuario nao encontrado.');
        $this->authorize('delete', $user);
        try {
            $user->delete();
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23503', '23000'], true)) {
                return response()->json(['message' => 'Usuario possui registros vinculados.'], 409);
            }
            throw $exception;
        }
        return response()->json(null, 204);
    }
}
