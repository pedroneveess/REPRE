<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Http\Requests\PaginationRequest;

class AnnouncementController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', Announcement::class);
        $user = auth()->user();
        $query = Announcement::with('schoolClass', 'creator');

        if ($user->system_role === 'teacher') {
            $query->whereHas('schoolClass.members', fn ($q) => $q->where('users.id', $user->id));
        } elseif (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->whereHas('schoolClass.members', fn ($q) => $q->where('users.id', $user->id));
        }

        return AnnouncementResource::collection($query->paginate($request->perPage()));
    }

    public function show(string $id)
    {
        $announcement = Announcement::with('schoolClass', 'creator')->find($id);
        abort_unless($announcement, 404, 'Aviso não encontrado.');
        $this->authorize('view', $announcement);
        return new AnnouncementResource($announcement);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $this->authorize('create', Announcement::class);
        $validated = $request->validated();

        if ($request->user()->system_role === 'teacher' && !$request->user()->schoolClasses()->whereKey($validated['class_id'])->exists()) {
            abort(403);
        }
        $validated['created_by'] = $request->user()->id;
        return (new AnnouncementResource(Announcement::create($validated)))->response()->setStatusCode(201);
    }

    public function update(UpdateAnnouncementRequest $request, string $id)
    {
        $announcement = Announcement::find($id);
        abort_unless($announcement, 404, 'Aviso não encontrado.');
        $this->authorize('update', $announcement);
        $announcement->update($request->validated());
        return new AnnouncementResource($announcement->fresh());
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
