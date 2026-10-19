<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\CalendarEventResource;
use App\Models\SchoolClass;

class CalendarEventController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', CalendarEvent::class);
        $user = auth()->user();
        $query = CalendarEvent::with('schoolClass', 'creator');
        if (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->where(function ($events) use ($user) {
                $events->whereNull('class_id')->orWhereHas('schoolClass.members', fn ($members) => $members->where('users.id', $user->id));
            });
        }
        return CalendarEventResource::collection($query->paginate($request->perPage()));
    }

    public function show(string $id)
    {
        $event = CalendarEvent::with('schoolClass', 'creator')->find($id);
        abort_unless($event, 404, 'Evento nao encontrado.');
        $this->authorize('view', $event);
        return new CalendarEventResource($event);
    }

    public function store(StoreCalendarEventRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        $schoolClass = !empty($validated['class_id']) ? SchoolClass::findOrFail($validated['class_id']) : null;
        if ($schoolClass) {
            $this->authorize('create', [CalendarEvent::class, $schoolClass]);
        } elseif ($user->system_role !== 'admin') {
            abort(403);
        }
        if ($user->system_role === 'teacher' && empty($validated['class_id'])) {
            abort(403);
        }
        $validated['created_by'] = $user->id;
        return (new CalendarEventResource(CalendarEvent::create($validated)))->response()->setStatusCode(201);
    }

    public function update(UpdateCalendarEventRequest $request, string $id)
    {
        $event = CalendarEvent::find($id);
        abort_unless($event, 404, 'Evento nao encontrado.');
        $this->authorize('update', $event);
        $validated = $request->validated();
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
        return new CalendarEventResource($event->fresh());
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
