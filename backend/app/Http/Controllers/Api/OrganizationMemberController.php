<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationMemberController extends Controller
{
    private const ROLES = ['member', 'administrator', 'president', 'coordinator'];

    public function index(string $organizationId)
    {
        $organization = Organization::findOrFail($organizationId);
        $this->authorize('view', $organization);
        return response()->json(['members' => $organization->members()->withPivot('role')->get()]);
    }

    public function store(Request $request, string $organizationId)
    {
        $organization = Organization::findOrFail($organizationId);
        $this->authorize('update', $organization);
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
        ]);
        $organization->members()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role']]]);
        return response()->json(['members' => $organization->members()->withPivot('role')->get()], 201);
    }

    public function update(Request $request, string $organizationId, string $userId)
    {
        $organization = Organization::findOrFail($organizationId);
        $this->authorize('update', $organization);
        abort_unless($organization->members()->whereKey($userId)->exists(), 404);
        $data = $request->validate(['role' => ['required', 'string', Rule::in(self::ROLES)]]);
        $organization->members()->updateExistingPivot($userId, ['role' => $data['role']]);
        return response()->json(['members' => $organization->members()->withPivot('role')->get()]);
    }

    public function destroy(string $organizationId, string $userId)
    {
        $organization = Organization::findOrFail($organizationId);
        $this->authorize('update', $organization);
        abort_unless($organization->members()->whereKey($userId)->exists(), 404);
        $organization->members()->detach($userId);
        return response()->json(null, 204);
    }
}
