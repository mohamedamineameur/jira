<?php

namespace App\Actions;

use App\Models\Comment;

class CreateCommentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Comment
    {
        return Comment::query()->create($data)->refresh();
    }
}
