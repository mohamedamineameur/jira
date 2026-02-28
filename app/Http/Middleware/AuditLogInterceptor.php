<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogInterceptor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldTrack($request)) {
            return $next($request);
        }

        $routeContext = $this->extractContextFromRoute($request);
        $before = $routeContext['before'] ?? null;

        $response = $next($request);

        if (! $this->isSuccessful($response)) {
            return $response;
        }

        $entityType = $routeContext['entity_type'] ?? null;
        $entityId = $routeContext['entity_id'] ?? null;
        $after = $this->extractAfterSnapshot($response);

        if ((! is_string($entityType) || ! is_string($entityId)) && is_array($after)) {
            $resolved = $this->extractContextFromPayload($request, $after);
            $entityType = $entityType ?? $resolved['entity_type'];
            $entityId = $entityId ?? $resolved['entity_id'];
        }

        if (! is_string($entityType) || ! is_string($entityId)) {
            return $response;
        }

        AuditLog::query()->create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => strtolower($request->method()),
            'performed_by' => $request->user()?->id,
            'before' => is_array($before) ? $before : null,
            'after' => is_array($after) ? $after : null,
            'ip_address' => $request->ip(),
        ]);

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        return ! str_contains($request->path(), 'audit-logs');
    }

    private function isSuccessful(Response $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    /**
     * @return array{entity_type?: string, entity_id?: string, before?: array<string, mixed>}
     */
    private function extractContextFromRoute(Request $request): array
    {
        $route = $request->route();
        if (! $route) {
            return [];
        }

        $organization = $route->parameter('organization');
        $user = $route->parameter('user');
        if (str_contains($request->path(), '/members/') && $organization instanceof Model && $user instanceof Model) {
            return [
                'entity_type' => 'organization_member',
                'entity_id' => $organization->getKey().':'.$user->getKey(),
            ];
        }

        $priorities = ['comment', 'ticket', 'label', 'project', 'invitation', 'organization', 'admin', 'user', 'session'];
        foreach ($priorities as $key) {
            $param = $route->parameter($key);
            if ($param instanceof Model) {
                return [
                    'entity_type' => $key,
                    'entity_id' => (string) $param->getKey(),
                    'before' => $param->toArray(),
                ];
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractAfterSnapshot(Response $response): ?array
    {
        if (! $response instanceof JsonResponse) {
            return null;
        }

        $payload = $response->getData(true);
        if (! is_array($payload)) {
            return null;
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $after
     * @return array{entity_type: string|null, entity_id: string|null}
     */
    private function extractContextFromPayload(Request $request, array $after): array
    {
        if (isset($after['ticket_id'], $after['label_id'])) {
            return [
                'entity_type' => 'ticket_label',
                'entity_id' => (string) $after['ticket_id'].':'.(string) $after['label_id'],
            ];
        }

        if (isset($after['organization_id'], $after['user_id']) && str_contains($request->path(), '/members')) {
            return [
                'entity_type' => 'organization_member',
                'entity_id' => (string) $after['organization_id'].':'.(string) $after['user_id'],
            ];
        }

        if (isset($after['id']) && is_string($after['id'])) {
            return [
                'entity_type' => $this->inferEntityTypeFromPath($request->path()),
                'entity_id' => $after['id'],
            ];
        }

        return [
            'entity_type' => null,
            'entity_id' => null,
        ];
    }

    private function inferEntityTypeFromPath(string $path): ?string
    {
        $map = [
            'comments' => 'comment',
            'tickets' => 'ticket',
            'labels' => 'label',
            'projects' => 'project',
            'invitations' => 'invitation',
            'organizations' => 'organization',
            'admins' => 'admin',
            'users' => 'user',
            'sessions' => 'session',
        ];

        foreach ($map as $segment => $type) {
            if (str_contains($path, $segment)) {
                return $type;
            }
        }

        return null;
    }
}
