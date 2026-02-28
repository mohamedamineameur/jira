<?php

namespace App\Actions;

use App\Models\Comment;

class DeleteCommentAction
{
    public function execute(Comment $comment): Comment
    {
        $comment->is_deleted = true;
        $comment->deleted_at = now();
        $comment->save();

        return $comment->refresh();
    }
}
