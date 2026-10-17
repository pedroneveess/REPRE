<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassMemberController extends Controller
{
    private const ROLES = ['student', 'representative', 'teacher'];

    public function index(string $classId)
    {
        $class = SchoolClass::findOrFail($classId);
        $this->authorize('view', $class);
        return response()->json(['members' => $class->members()->withPivot('role')->get()]);
    }

    public function store(Request $request, string $classId)
    {
        $class = SchoolClass::findOrFail($classId);
        $this->authorize('update', $class);
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
        ]);
        $class->members()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role']]]);
        return response()->json(['members' => $class->members()->withPivot('role')->get()], 201);
    }

    public function update(Request $request, string $classId, string $userId)
    {
        $class = SchoolClass::findOrFail($classId);
        $this->authorize('update', $class);
        abort_unless($class->members()->whereKey($userId)->exists(), 404);
        $data = $request->validate(['role' => ['required', 'string', Rule::in(self::ROLES)]]);
        $class->members()->updateExistingPivot($userId, ['role' => $data['role']]);
        return response()->json(['members' => $class->members()->withPivot('role')->get()]);
    }

    public function destroy(string $classId, string $userId)
    {
        $class = SchoolClass::findOrFail($classId);
        $this->authorize('update', $class);
        abort_unless($class->members()->whereKey($userId)->exists(), 404);
        $class->members()->detach($userId);
        return response()->json(null, 204);
    }
}
