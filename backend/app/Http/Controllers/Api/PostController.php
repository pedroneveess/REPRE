<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Requests\PaginationRequest;

class PostController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $this->authorize('viewAny', Post::class);
        $user = auth()->user();
        $query = Post::with('organization', 'creator');
        if ($user->system_role === 'teacher') {
            $query->whereHas('organization.members', fn ($q) => $q->where('users.id', $user->id));
        } elseif (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->whereHas('organization.members', fn ($q) => $q->where('users.id', $user->id));
        }
        return PostResource::collection($query->paginate($request->perPage()));
    }

    public function show(string $id)
    {
        $post = Post::with('organization', 'creator')->find($id);
        abort_unless($post, 404, 'Post não encontrado.');
        $this->authorize('view', $post);
        return new PostResource($post);
    }

    public function store(StorePostRequest $request)
    {
        $this->authorize('create', Post::class);
        $validated = $request->validated();
        if ($request->user()->system_role === 'teacher' && !$request->user()->organizations()->whereKey($validated['organization_id'])->exists()) {
            abort(403);
        }
        $validated['created_by'] = $request->user()->id;
        return (new PostResource(Post::create($validated)))->response()->setStatusCode(201);
    }

    public function update(UpdatePostRequest $request, string $id)
    {
        $post = Post::find($id);
        abort_unless($post, 404, 'Post não encontrado.');
        $this->authorize('update', $post);
        $post->update($request->validated());
        return new PostResource($post->fresh());
    }

    public function destroy(string $id)
    {
        $post = Post::find($id);
        abort_unless($post, 404, 'Post não encontrado.');
        $this->authorize('delete', $post);
        $post->delete();
        return response()->json(null, 204);
    }
}
