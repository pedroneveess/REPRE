<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Requests\PaginationRequest;

class OrganizationController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', Organization::class);
        $user = auth()->user();
        $query = Organization::with('members', 'posts');
        if ($user->system_role === 'teacher') {
            $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
        } elseif (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
        }
        return OrganizationResource::collection($query->paginate($request->perPage()));
    }

    public function show(string $id)
    {
        $organization = Organization::with('members', 'posts')->find($id);
        abort_unless($organization, 404, 'Organização não encontrada.');
        $this->authorize('view', $organization);
        return new OrganizationResource($organization);
    }

    public function store(StoreOrganizationRequest $request)
    {
        $this->authorize('create', Organization::class);
        $validated = $request->validated();
        $organization = Organization::create($validated);
        if ($request->user()->system_role === 'teacher') {
            $organization->members()->attach($request->user()->id, ['role' => 'teacher']);
        }
        return (new OrganizationResource($organization->load('members')))->response()->setStatusCode(201);
    }

    public function update(UpdateOrganizationRequest $request, string $id)
    {
        $organization = Organization::find($id);
        abort_unless($organization, 404, 'Organização não encontrada.');
        $this->authorize('update', $organization);
        $organization->update($request->validated());
        return new OrganizationResource($organization->fresh());
    }

    public function destroy(string $id)
    {
        $organization = Organization::find($id);
        abort_unless($organization, 404, 'Organização não encontrada.');
        $this->authorize('delete', $organization);
        $organization->delete();
        return response()->json(null, 204);
    }
}
