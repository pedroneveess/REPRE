<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Http\Requests\MemberRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Http\Resources\UserResource;

class ClassMemberController extends Controller
{
    private const ROLES = ['student', 'representative', 'teacher'];

    public function index(PaginationRequest $request, string $school_class)
    {
        $class = SchoolClass::findOrFail($school_class);
        $this->authorize('view', $class);
        $members = $class->members()->withPivot('role');
        $user = $request->user();
        if ($user->system_role !== 'admin' && !($user->system_role === 'teacher' && $class->members()->whereKey($user->id)->where('class_members.role', 'teacher')->exists())) {
            $members->whereKey($user->id);
        }
        return UserResource::collection($members->paginate($request->perPage()));
    }

    public function store(MemberRequest $request, string $school_class)
    {
        $class = SchoolClass::findOrFail($school_class);
        $this->authorize('update', $class);
        $data = $request->validated();
        $class->members()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role']]]);
        return (new UserResource($class->members()->withPivot('role')->find($data['user_id'])))->response()->setStatusCode(201);
    }

    public function update(UpdateMemberRoleRequest $request, string $school_class, string $user_id)
    {
        $class = SchoolClass::findOrFail($school_class);
        $this->authorize('update', $class);
        abort_unless($class->members()->whereKey($user_id)->exists(), 404);
        $data = $request->validated();
        $class->members()->updateExistingPivot($user_id, ['role' => $data['role']]);
        return new UserResource($class->members()->withPivot('role')->find($user_id));
    }

    public function destroy(string $school_class, string $user_id)
    {
        $class = SchoolClass::findOrFail($school_class);
        $this->authorize('update', $class);
        abort_unless($class->members()->whereKey($user_id)->exists(), 404);
        $class->members()->detach($user_id);
        return response()->json(null, 204);
    }
}
