<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostController extends Controller
{
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'content' => 'required|string|max:65000',
        ]);

        /** @var User $user */
        $user = auth()->user();

        /** @var Post $post */
        $post = $user->posts()->create($data);

        return \response($post->toArray());
    }

    public function list(): Response
    {
        /** @var User $user */
        $user = auth()->user();
        $posts = $user->posts()->cursorPaginate();

        return $this->responseCursorPagination($posts);
    }

    public function edit(Post $post, Request $request): Response
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'content' => 'required|string|max:65000',
        ]);

        /** @var User $user */
        $user = auth()->user();
        if ($post->id_user !== $user->getKey()) {
            throw new NotFoundHttpException();
        }

        $post->update($data);

        return \response($post->toArray());
    }

    public function delete(Post $post): Response
    {
        /** @var User $user */
        $user = auth()->user();
        if ($post->id_user !== $user->getKey() && !$user->is_admin) {
            throw new NotFoundHttpException();
        }

        $post->delete();

        return \response(null);
    }

    public function storeComment(Post $post, Request $request): Response
    {
        $data = $request->validate([
            'comment' => 'required|string|max:65000',
            'id_parent' => 'nullable|int',
        ]);

        $parent = null;
        if (!empty($data['id_parent'])) {
            /** @var Comment $parent */
            $parent = $post->comments()->whereNull('id_parent')->whereKey($data['id_parent'])->first();
            if (empty($parent)) {
                throw new BadRequestHttpException('invalid parent comment');
            }
        }

        /** @var User $user */
        $user = auth()->user();

        $comment = $post->comments()->create([
            'id_user' => $user->getKey(),
            'comment' => $data['comment'],
            'id_parent' => $parent?->getKey(),
        ]);

        return \response($comment->toArray());
    }

    public function listComment(Post $post, Request $request): Response
    {
        $comments = $post->comments()->with('replies')->whereNull('id_parent')->cursorPaginate();

        return $this->responseCursorPagination($comments);
    }
}
