<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Http\Requests\MemberRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\UserResource;

class ClassMemberController extends Controller
{
    private const ROLES = ['student', 'representative', 'teacher'];

    public function index(PaginationRequest $request, string $school_class)
    {
        $class = SchoolClass::findOrFail($school_class);
        $this->authorize('view', $class);
        return UserResource::collection($class->members()->withPivot('role')->paginate($request->perPage()));
    }

    public function store(MemberRequest $request, string $school_class)
    {
        $class = SchoolClass::findOrFail($school_class);
        $this->authorize('update', $class);
        $data = $request->validated();
        $class->members()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role']]]);
        return (new UserResource($class->members()->withPivot('role')->find($data['user_id'])))->response()->setStatusCode(201);
    }

    public function update(MemberRequest $request, string $school_class, string $user_id)
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
