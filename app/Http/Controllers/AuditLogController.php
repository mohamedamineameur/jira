<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);

        $logs = AuditLog::query()
            ->with('performer')
            ->where('is_deleted', false)
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($logs);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        if ($auditLog->is_deleted) {
            abort(404);
        }

        return response()->json([
            'data' => $auditLog->load('performer'),
        ]);
    }
}
