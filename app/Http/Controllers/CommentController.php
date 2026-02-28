<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $commentService)
    {
    }

    public function index(
        Request $request,
        Organization $organization,
        Project $project,
        Ticket $ticket
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidPath($organization, $project, $ticket)) {
            abort(404);
        }

        $perPage = (int) $request->integer('per_page', 15);
        $comments = $this->commentService->paginate($ticket, $perPage);

        return response()->json($comments);
    }

    public function store(
        StoreCommentRequest $request,
        Organization $organization,
        Project $project,
        Ticket $ticket
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidPath($organization, $project, $ticket)) {
            abort(404);
        }

        /** @var User $authUser */
        $authUser = $request->user();
        $comment = $this->commentService->create($ticket, $request->validated()['content'], $authUser->id);

        return response()->json([
            'data' => $comment->load(['author', 'ticket']),
        ], 201);
    }

    public function show(
        Organization $organization,
        Project $project,
        Ticket $ticket,
        Comment $comment
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidCommentPath($organization, $project, $ticket, $comment)) {
            abort(404);
        }

        return response()->json([
            'data' => $comment->load(['author', 'ticket']),
        ]);
    }

    public function update(
        UpdateCommentRequest $request,
        Organization $organization,
        Project $project,
        Ticket $ticket,
        Comment $comment
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidCommentPath($organization, $project, $ticket, $comment)) {
            abort(404);
        }

        $updatedComment = $this->commentService->update($comment, $request->validated()['content']);

        return response()->json([
            'data' => $updatedComment->load(['author', 'ticket']),
        ]);
    }

    public function destroy(
        Organization $organization,
        Project $project,
        Ticket $ticket,
        Comment $comment
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidCommentPath($organization, $project, $ticket, $comment)) {
            abort(404);
        }

        $deletedComment = $this->commentService->delete($comment);

        return response()->json([
            'message' => 'Comment deleted successfully.',
            'data' => $deletedComment,
        ]);
    }

    private function isValidPath(Organization $organization, Project $project, Ticket $ticket): bool
    {
        return $project->organization_id === $organization->id
            && ! $project->is_deleted
            && $ticket->project_id === $project->id
            && ! $ticket->is_deleted;
    }

    private function isValidCommentPath(
        Organization $organization,
        Project $project,
        Ticket $ticket,
        Comment $comment
    ): bool {
        return $this->isValidPath($organization, $project, $ticket)
            && $comment->ticket_id === $ticket->id
            && ! $comment->is_deleted;
    }
}
