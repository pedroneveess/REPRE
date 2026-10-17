<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;

class CalendarEventController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', CalendarEvent::class);
        $user = auth()->user();
        $query = CalendarEvent::with('schoolClass', 'creator');
        if (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->where(function ($events) use ($user) {
                $events->whereNull('class_id')->orWhereHas('schoolClass.members', fn ($members) => $members->where('users.id', $user->id));
            });
        }
        return response()->json(['calendar_events' => $query->get()]);
    }

    public function show(string $id)
    {
        $event = CalendarEvent::with('schoolClass', 'creator')->find($id);
        abort_unless($event, 404, 'Evento nao encontrado.');
        $this->authorize('view', $event);
        return response()->json(['calendar_event' => $event]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', CalendarEvent::class);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'class_id' => ['sometimes', 'nullable', 'uuid', 'exists:school_classes,id'],
        ]);
        $user = $request->user();
        if ($user->system_role === 'teacher' && empty($validated['class_id'])) {
            abort(403);
        }
        if ($user->system_role === 'teacher' && !$user->schoolClasses()->whereKey($validated['class_id'])->exists()) {
            abort(403);
        }
        $validated['created_by'] = $user->id;
        return response()->json(['calendar_event' => CalendarEvent::create($validated)], 201);
    }

    public function update(Request $request, string $id)
    {
        $event = CalendarEvent::find($id);
        abort_unless($event, 404, 'Evento nao encontrado.');
        $this->authorize('update', $event);
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'string'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'class_id' => ['sometimes', 'nullable', 'uuid', 'exists:school_classes,id'],
        ]);
        if (array_key_exists('class_id', $validated) && $request->user()->system_role === 'teacher') {
            if (empty($validated['class_id']) || !$request->user()->schoolClasses()->whereKey($validated['class_id'])->exists()) {
                abort(403);
            }
        }
        if (array_key_exists('starts_at', $validated) || array_key_exists('ends_at', $validated)) {
            $startsAt = $validated['starts_at'] ?? $event->starts_at;
            $endsAt = array_key_exists('ends_at', $validated) ? $validated['ends_at'] : $event->ends_at;
        } else {
            $endsAt = null;
        }
        if ($endsAt !== null) {
            validator(['starts_at' => $startsAt, 'ends_at' => $endsAt], [
                'ends_at' => ['after_or_equal:starts_at'],
            ])->validate();
        }
        $event->update($validated);
        return response()->json(['calendar_event' => $event->fresh()]);
    }

    public function destroy(string $id)
    {
        $event = CalendarEvent::find($id);
        abort_unless($event, 404, 'Evento nao encontrado.');
        $this->authorize('delete', $event);
        $event->delete();
        return response()->json(null, 204);
    }
}
