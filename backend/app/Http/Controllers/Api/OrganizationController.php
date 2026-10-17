<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Organization::class);
        $user = auth()->user();
        $query = Organization::with('members', 'posts');
        if ($user->system_role === 'teacher') {
            $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
        } elseif (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
        }
        return response()->json(['organizations' => $query->get()]);
    }

    public function show(string $id)
    {
        $organization = Organization::with('members', 'posts')->find($id);
        abort_unless($organization, 404, 'Organização não encontrada.');
        $this->authorize('view', $organization);
        return response()->json(['organization' => $organization]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Organization::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'type' => ['sometimes', 'string', 'max:50'],
        ]);
        $organization = Organization::create($validated);
        $organization->members()->attach($request->user()->id, ['role' => 'administrator']);
        return response()->json(['message' => 'Organização criada com sucesso.', 'organization' => $organization->load('members')], 201);
    }

    public function update(Request $request, string $id)
    {
        $organization = Organization::find($id);
        abort_unless($organization, 404, 'Organização não encontrada.');
        $this->authorize('update', $organization);
        $organization->update($request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'string'],
            'type' => ['sometimes', 'string', 'max:50'],
        ]));
        return response()->json(['message' => 'Organização atualizada com sucesso.', 'organization' => $organization->fresh()]);
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
