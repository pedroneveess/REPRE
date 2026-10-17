<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Policies\SchoolClassPolicy;

class SchoolClassesController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SchoolClass::class);

        $user = auth()->user();
        $classes = SchoolClass::with('members', 'tasks', 'announcements')->get();

        if (!in_array($user->system_role, ['coordinator', 'admin'])) {
            $classes = $classes->filter(function ($class) use ($user) {
                return $class->members->contains('id', $user->id);
            })->values();
        }

        return response()->json([
            'school_classes' => $classes
        ], 200);
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

        return response()->json([
            'school_class' => $schoolClass
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SchoolClass::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'course' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'integer', 'between:1,8'],
            'year' => ['required', 'integer', 'between:2000,' . date('Y')],
        ]);

        $schoolClass = SchoolClass::create($validated);

        if ($request->user()->system_role === 'teacher') {
            $schoolClass->members()->syncWithoutDetaching([
                $request->user()->id => ['role' => 'teacher'],
            ]);
        }

        return response()->json([
            'message' => 'Turma criada com sucesso.',
            'school_class' => $schoolClass
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $schoolClass = SchoolClass::find($id);

        if (!$schoolClass) {
            return response()->json([
                'message' => 'Turma não encontrada.'
            ], 404);
        }

        $this->authorize('update', $schoolClass);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'course' => ['sometimes', 'string', 'max:100'],
            'semester' => ['sometimes', 'integer', 'between:1,8'],
            'year' => ['sometimes', 'integer', 'between:2000'],
        ]);

        $schoolClass->update($validated);

        return response()->json([
            'message' => 'Turma atualizada com sucesso.',
            'school_class' => $schoolClass->fresh()
        ], 200);
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
