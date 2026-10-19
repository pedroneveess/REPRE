<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Http\Requests\OrganizationMemberRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\UserResource;

class OrganizationMemberController extends Controller
{
    private const ROLES = ['member', 'administrator', 'president', 'coordinator'];

    public function index(PaginationRequest $request, string $organization)
    {
        $organization = Organization::findOrFail($organization);
        $this->authorize('view', $organization);
        return UserResource::collection($organization->members()->withPivot('role')->paginate($request->perPage()));
    }

    public function store(OrganizationMemberRequest $request, string $organization)
    {
        $organization = Organization::findOrFail($organization);
        $this->authorize('update', $organization);
        $data = $request->validated();
        $organization->members()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role']]]);
        return (new UserResource($organization->members()->withPivot('role')->find($data['user_id'])))->response()->setStatusCode(201);
    }

    public function update(OrganizationMemberRequest $request, string $organization, string $user_id)
    {
        $organization = Organization::findOrFail($organization);
        $this->authorize('update', $organization);
        abort_unless($organization->members()->whereKey($user_id)->exists(), 404);
        $data = $request->validated();
        $organization->members()->updateExistingPivot($user_id, ['role' => $data['role']]);
        return new UserResource($organization->members()->withPivot('role')->find($user_id));
    }

    public function destroy(string $organization, string $user_id)
    {
        $organization = Organization::findOrFail($organization);
        $this->authorize('update', $organization);
        abort_unless($organization->members()->whereKey($user_id)->exists(), 404);
        $organization->members()->detach($user_id);
        return response()->json(null, 204);
    }
}
