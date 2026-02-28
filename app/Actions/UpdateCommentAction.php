<?php

namespace App\Actions;

use App\Models\Comment;

class UpdateCommentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Comment $comment, array $data): Comment
    {
        $comment->fill($data);
        $comment->save();

        return $comment->refresh();
    }
}
