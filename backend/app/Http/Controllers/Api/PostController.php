<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Post::class);
        $user = auth()->user();
        $query = Post::with('organization', 'creator');
        if ($user->system_role === 'teacher') {
            $query->whereHas('organization.members', fn ($q) => $q->where('users.id', $user->id));
        } elseif (!in_array($user->system_role, ['admin', 'coordinator'], true)) {
            $query->whereHas('organization.members', fn ($q) => $q->where('users.id', $user->id));
        }
        return response()->json(['posts' => $query->get()]);
    }

    public function show(string $id)
    {
        $post = Post::with('organization', 'creator')->find($id);
        abort_unless($post, 404, 'Post não encontrado.');
        $this->authorize('view', $post);
        return response()->json(['post' => $post]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Post::class);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
        ]);
        if ($request->user()->system_role === 'teacher' && !$request->user()->organizations()->whereKey($validated['organization_id'])->exists()) {
            abort(403);
        }
        $validated['created_by'] = $request->user()->id;
        return response()->json(['message' => 'Post criado com sucesso.', 'post' => Post::create($validated)], 201);
    }

    public function update(Request $request, string $id)
    {
        $post = Post::find($id);
        abort_unless($post, 404, 'Post não encontrado.');
        $this->authorize('update', $post);
        $post->update($request->validate([
            'title' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'string'],
        ]));
        return response()->json(['message' => 'Post atualizado com sucesso.', 'post' => $post->fresh()]);
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
