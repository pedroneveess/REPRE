<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Http\Requests\StoreSchoolClassRequest;
use App\Http\Requests\UpdateSchoolClassRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\SchoolClassResource;

class SchoolClassController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', SchoolClass::class);

        $user = auth()->user();
        $query = SchoolClass::query();

        if (!in_array($user->system_role, ['coordinator', 'admin'])) {
            $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
        }
        return SchoolClassResource::collection($query->paginate($request->perPage()));
    }

    public function show(string $id)
    {
        $schoolClass = SchoolClass::with('members', 'tasks', 'announcements')->find($id);

        if (!$schoolClass) {
            return response()->json([
                'message' => 'Turma não encontrada.'
            ], 404);
        }

        $this->authorize('view', $schoolClass);

        return new SchoolClassResource($schoolClass);
    }

    public function store(StoreSchoolClassRequest $request)
    {
        $this->authorize('create', SchoolClass::class);

        $validated = $request->validated();

        $schoolClass = SchoolClass::create($validated);

        if ($request->user()->system_role === 'teacher') {
            $schoolClass->members()->syncWithoutDetaching([
                $request->user()->id => ['role' => 'teacher'],
            ]);
        }

        return (new SchoolClassResource($schoolClass))->response()->setStatusCode(201);
    }

    public function update(UpdateSchoolClassRequest $request, string $id)
    {
        $schoolClass = SchoolClass::find($id);

        if (!$schoolClass) {
            return response()->json([
                'message' => 'Turma não encontrada.'
            ], 404);
        }

        $this->authorize('update', $schoolClass);

        $validated = $request->validated();

        $schoolClass->update($validated);

        return new SchoolClassResource($schoolClass->fresh());
    }

    public function destroy(string $id)
    {
        $schoolClass = SchoolClass::find($id);

        if (!$schoolClass) {
            return response()->json([
                'message' => 'Turma não encontrada.'
            ], 404);
        }

        $this->authorize('delete', $schoolClass);

        $schoolClass->delete();

        return response()->json(null, 204);
    }
}
