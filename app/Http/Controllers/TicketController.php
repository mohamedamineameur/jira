<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    public function index(Request $request, Organization $organization, Project $project): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if ($project->organization_id !== $organization->id || $project->is_deleted) {
            abort(404);
        }

        $perPage = (int) $request->integer('per_page', 15);
        $tickets = $this->ticketService->paginate($project, $perPage);

        return response()->json($tickets);
    }

    public function store(
        StoreTicketRequest $request,
        Organization $organization,
        Project $project
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if ($project->organization_id !== $organization->id || $project->is_deleted) {
            abort(404);
        }

        /** @var User $authUser */
        $authUser = $request->user();

        $ticket = $this->ticketService->create($project, $request->validated(), $authUser->id);

        return response()->json([
            'data' => $ticket->load(['project', 'assignee', 'reporter']),
        ], 201);
    }

    public function show(Organization $organization, Project $project, Ticket $ticket): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if (
            $project->organization_id !== $organization->id
            || $project->is_deleted
            || $ticket->project_id !== $project->id
            || $ticket->is_deleted
        ) {
            abort(404);
        }

        return response()->json([
            'data' => $ticket->load(['project', 'assignee', 'reporter']),
        ]);
    }

    public function update(
        UpdateTicketRequest $request,
        Organization $organization,
        Project $project,
        Ticket $ticket
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (
            $project->organization_id !== $organization->id
            || $project->is_deleted
            || $ticket->project_id !== $project->id
            || $ticket->is_deleted
        ) {
            abort(404);
        }

        $updatedTicket = $this->ticketService->update($ticket, $request->validated());

        return response()->json([
            'data' => $updatedTicket->load(['project', 'assignee', 'reporter']),
        ]);
    }

    public function destroy(Organization $organization, Project $project, Ticket $ticket): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if (
            $project->organization_id !== $organization->id
            || $project->is_deleted
            || $ticket->project_id !== $project->id
            || $ticket->is_deleted
        ) {
            abort(404);
        }

        $deletedTicket = $this->ticketService->delete($ticket);

        return response()->json([
            'message' => 'Ticket deleted successfully.',
            'data' => $deletedTicket,
        ]);
    }
}
