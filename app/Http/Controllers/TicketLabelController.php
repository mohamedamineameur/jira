<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketLabelRequest;
use App\Models\Label;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Ticket;
use App\Services\TicketLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketLabelController extends Controller
{
    public function __construct(private readonly TicketLabelService $ticketLabelService)
    {
    }

    public function index(
        Request $request,
        Organization $organization,
        Project $project,
        Ticket $ticket
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidTicketPath($organization, $project, $ticket)) {
            abort(404);
        }

        $perPage = (int) $request->integer('per_page', 15);
        $ticketLabels = $this->ticketLabelService->paginate($ticket, $perPage);

        return response()->json($ticketLabels);
    }

    public function store(
        StoreTicketLabelRequest $request,
        Organization $organization,
        Project $project,
        Ticket $ticket
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidTicketPath($organization, $project, $ticket)) {
            abort(404);
        }

        $label = Label::query()->findOrFail($request->validated()['label_id']);
        if ($label->project_id !== $project->id || $label->is_deleted) {
            abort(422, 'Label must belong to the same project.');
        }

        $ticketLabel = $this->ticketLabelService->create($ticket, $label);

        return response()->json([
            'data' => $ticketLabel->load(['ticket', 'label']),
        ], 201);
    }

    public function destroy(
        Organization $organization,
        Project $project,
        Ticket $ticket,
        Label $label
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidTicketPath($organization, $project, $ticket)) {
            abort(404);
        }

        $ticketLabel = $this->ticketLabelService->find($ticket, $label);
        if (! $ticketLabel) {
            abort(404);
        }

        $deleted = $this->ticketLabelService->delete($ticketLabel);

        return response()->json([
            'message' => 'Ticket label removed successfully.',
            'data' => $deleted->load(['ticket', 'label']),
        ]);
    }

    private function isValidTicketPath(Organization $organization, Project $project, Ticket $ticket): bool
    {
        return $project->organization_id === $organization->id
            && ! $project->is_deleted
            && $ticket->project_id === $project->id
            && ! $ticket->is_deleted;
    }
}
