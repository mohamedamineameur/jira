<?php

namespace App\Services;

use App\Actions\CreateCommentAction;
use App\Actions\DeleteCommentAction;
use App\Actions\UpdateCommentAction;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentService
{
    public function __construct(
        private readonly CreateCommentAction $createCommentAction,
        private readonly UpdateCommentAction $updateCommentAction,
        private readonly DeleteCommentAction $deleteCommentAction,
    ) {
    }

    public function paginate(Ticket $ticket, int $perPage = 15): LengthAwarePaginator
    {
        return Comment::query()
            ->with(['author', 'ticket'])
            ->where('ticket_id', $ticket->id)
            ->where('is_deleted', false)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function create(Ticket $ticket, string $content, string $authorId): Comment
    {
        return $this->createCommentAction->execute([
            'ticket_id' => $ticket->id,
            'author_id' => $authorId,
            'content' => $content,
        ]);
    }

    public function update(Comment $comment, string $content): Comment
    {
        return $this->updateCommentAction->execute($comment, [
            'content' => $content,
        ]);
    }

    public function delete(Comment $comment): Comment
    {
        return $this->deleteCommentAction->execute($comment);
    }
}
