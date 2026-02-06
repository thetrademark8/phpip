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
        $type = $request->input('type');
        $data = $request->input('data', []);

        if (!$type) {
            return response()->json(['error' => 'Missing event type'], 400);
        }

        Log::info('TeamLeader webhook received', [
            'type' => $type,
            'data' => $data,
        ]);

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
                'entity' => $entity,
                'action' => $action,
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('TeamLeader webhook error', [
                'type' => $type,
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
