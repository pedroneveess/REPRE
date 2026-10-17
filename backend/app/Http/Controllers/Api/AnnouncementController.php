<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Announcement::class);
        $user = auth()->user();
        $query = Announcement::with('schoolClass', 'creator');

        if ($user->system_role === 'teacher') {
            $query->whereHas('schoolClass.members', fn ($q) => $q->where('users.id', $user->id));
        } elseif (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->whereHas('schoolClass.members', fn ($q) => $q->where('users.id', $user->id));
        }

        return response()->json(['announcements' => $query->get()]);
    }

    public function show(string $id)
    {
        $announcement = Announcement::with('schoolClass', 'creator')->find($id);
        abort_unless($announcement, 404, 'Aviso não encontrado.');
        $this->authorize('view', $announcement);
        return response()->json(['announcement' => $announcement]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Announcement::class);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
            'class_id' => ['required', 'uuid', 'exists:school_classes,id'],
        ]);
        if ($request->user()->system_role === 'teacher' && !$request->user()->schoolClasses()->whereKey($validated['class_id'])->exists()) {
            abort(403);
        }
        $validated['created_by'] = $request->user()->id;
        return response()->json(['message' => 'Aviso criado com sucesso.', 'announcement' => Announcement::create($validated)], 201);
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::find($id);
        abort_unless($announcement, 404, 'Aviso não encontrado.');
        $this->authorize('update', $announcement);
        $announcement->update($request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'content' => ['sometimes', 'string'],
        ]));
        return response()->json(['message' => 'Aviso atualizado com sucesso.', 'announcement' => $announcement->fresh()]);
    }

    public function destroy(string $id)
    {
        $announcement = Announcement::find($id);
        abort_unless($announcement, 404, 'Aviso não encontrado.');
        $this->authorize('delete', $announcement);
        $announcement->delete();
        return response()->json(null, 204);
    }
}
