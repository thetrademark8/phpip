<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeamLeader\Handlers\CompanyHandler;
use App\Services\TeamLeader\Handlers\ContactHandler;
use App\Services\TeamLeader\TeamLeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeamLeaderWebhookController extends Controller
{
    protected array $handlers = [
        'company' => CompanyHandler::class,
        'contact' => ContactHandler::class,
    ];

    public function __construct(
        protected TeamLeaderService $teamLeaderService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('TeamLeader webhook received', ['payload' => $payload]);

        // Teamleader sends either "type" at root level, or the event type can be inferred
        $type = $payload['type'] ?? null;

        if (!$type) {
            Log::warning('TeamLeader: Missing event type', ['payload' => $payload]);
            return response()->json(['error' => 'Missing event type'], 400);
        }

        // Build the data array with the subject ID
        // Teamleader webhooks send: { "subject": { "type": "contact", "id": "uuid" }, "type": "contact.added" }
        // Our handlers expect: { "id": "uuid" }
        $data = $payload['data'] ?? [];
        $subject = $payload['subject'] ?? [];

        if (!isset($data['id']) && isset($subject['id'])) {
            $data['id'] = $subject['id'];
        }

        if (!isset($data['id'])) {
            Log::error('TeamLeader: No entity ID found in webhook payload', [
                'type' => $type,
                'payload' => $payload,
            ]);
            return response()->json(['error' => 'No entity ID in payload'], 400);
        }

        [$entity, $action] = $this->parseEventType($type);

        if (!$entity || !$action) {
            Log::warning('TeamLeader: Unknown event type', ['type' => $type]);
            return response()->json(['error' => 'Unknown event type'], 400);
        }

        $handlerClass = $this->handlers[$entity] ?? null;

        if (!$handlerClass) {
            Log::warning('TeamLeader: No handler for entity', ['entity' => $entity]);
            return response()->json(['error' => 'No handler for entity'], 400);
        }

        $method = $this->normalizeAction($action);

        if (!method_exists($handlerClass, $method)) {
            Log::warning('TeamLeader: Handler method not found', [
                'handler' => $handlerClass,
                'method' => $method,
            ]);
            return response()->json(['error' => 'Handler method not found'], 400);
        }

        try {
            $handlerClass::$method($data, $this->teamLeaderService);

            Log::info('TeamLeader webhook processed', [
                'type' => $type,
                'id' => $data['id'],
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('TeamLeader webhook error', [
                'type' => $type,
                'id' => $data['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    protected function parseEventType(string $type): array
    {
        $parts = explode('.', $type);

        if (count($parts) < 2) {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    protected function normalizeAction(string $action): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $action))));
    }
}
