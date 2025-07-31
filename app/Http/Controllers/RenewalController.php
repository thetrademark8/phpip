<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\Renewal\RenewalFilterDTO;
use App\Services\Renewal\Contracts\RenewalQueryServiceInterface;
use App\Services\Renewal\Contracts\RenewalFeeCalculatorInterface;
use App\Services\Renewal\Contracts\RenewalWorkflowServiceInterface;
use App\Services\Renewal\Contracts\RenewalEmailServiceInterface;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RenewalController extends Controller
{
    public function __construct(
        private RenewalQueryServiceInterface $queryService,
        private RenewalFeeCalculatorInterface $feeCalculator,
        private RenewalWorkflowServiceInterface $workflowService,
        private RenewalEmailServiceInterface $emailService,
        private RenewalRepositoryInterface $renewalRepository
    ) {}

    /**
     * Display a listing of renewals
     */
    public function index(Request $request)
    {
        // Create filter DTO from request
        $filters = RenewalFilterDTO::fromRequest($request);
        
        // Build query using service
        $query = $this->queryService->buildQuery($filters);
        
        // Paginate results
        $renewals = $this->renewalRepository->paginate($query, $filters->perPage);
        
        // Transform renewals to include calculated fees
        $renewals->transform(function ($renewal) {
            $feeDTO = $this->feeCalculator->calculate(\App\DataTransferObjects\Renewal\RenewalDTO::fromModel($renewal));
            $renewal->cost = $feeDTO->cost;
            $renewal->fee = $feeDTO->fee;
            return $renewal;
        });
        
        // Keep URL parameters in paginator links
        $renewals->appends($request->input());
        
        // For API/JSON requests
        if ($request->wantsJson()) {
            return response()->json($renewals);
        }
        
        // For Inertia requests
        return Inertia::render('Renewal/Index', [
            'renewals' => $renewals,
            'filters' => $filters->toArray(),
            'step' => $filters->step,
            'invoice_step' => $filters->invoiceStep,
            'my_renewals' => $filters->myRenewals,
            'config' => [
                'receipt_tabs' => config('renewal.general.receipt_tabs'),
                'invoice_backend' => config('renewal.invoice.backend'),
            ],
        ]);
    }

    /**
     * Send first call for selected renewals
     */
    public function firstcall(Request $request, int $send)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index')
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        // Send or preview first calls
        $result = $this->emailService->sendFirstCall($ids, $send !== 1);
        
        if ($result->success) {
            // Update step to 2 (reminder) if sent
            if ($send == 1) {
                $this->workflowService->updateStep($ids, 2);
            }
            
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index')
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Send reminder call for selected renewals
     */
    public function remindercall(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        $result = $this->emailService->sendReminderCall($ids);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 2])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Send last call for selected renewals
     */
    public function lastcall(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        $result = $this->emailService->sendLastCall($ids);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 2])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Send formal call for selected renewals
     */
    public function formalcall(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        $result = $this->emailService->sendFormalCall($ids);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 2])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Generate XML file for selected renewals (payment)
     */
    public function payment(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        // Update invoice step to 1 (invoiced)
        $result = $this->workflowService->updateInvoiceStep($ids, 1);
        
        if ($result->success) {
            // Generate XML file for payment
            $xmlContent = $this->generatePaymentXml($ids);
            
            return response($xmlContent, 200)
                ->header('Content-Type', 'text/xml')
                ->header('Content-Disposition', 'attachment; filename="payment_' . date('YmdHis') . '.xml"');
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 2])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Update fees for a renewal
     */
    public function updatefees(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:task,id',
            'cost' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
        ]);
        
        $success = $this->renewalRepository->updateFees(
            $validated['task_id'],
            $validated['cost'],
            $validated['fee']
        );
        
        if ($success) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()
                    ->with('success', 'Fees updated successfully');
            }
            return response()->json(['success' => 'Fees updated successfully']);
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update fees']);
        }
        return response()->json(['error' => 'Failed to update fees'], 500);
    }

    /**
     * Clear selected renewals
     */
    public function clear(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        $result = $this->workflowService->abandon($ids);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 2])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 2])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Generate invoice for selected renewals
     */
    public function invoice(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 3])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        $result = $this->emailService->sendInvoice($ids);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['invoice_step' => 2])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 3])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Register receipt for selected renewals
     */
    public function receipt(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['invoice_step' => 2])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        // Update invoice step to 3 (paid)
        $result = $this->workflowService->updateInvoiceStep($ids, 3);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['invoice_step' => 3])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['invoice_step' => 2])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Register receipts for selected renewals
     */
    public function receipts(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 4])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        // Update step to 5 (receipts)
        $result = $this->workflowService->updateStep($ids, 5);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 5])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 4])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Close selected renewals
     */
    public function closing(Request $request)
    {
        $ids = $request->input('task_ids', []);
        $done_date = $request->input('done_date');
        
        if (empty($ids)) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 5])
                    ->withErrors(['error' => 'No renewals selected']);
            }
            return response()->json(['error' => 'No renewals selected'], 400);
        }
        
        $result = $this->workflowService->markAsDone($ids, $done_date);
        
        if ($result->success) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('renewal.index', ['step' => 10])
                    ->with('success', $result->message);
            }
            return response()->json($result->toArray());
        }
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('renewal.index', ['step' => 5])
                ->withErrors(['error' => $result->error]);
        }
        return response()->json($result->toArray(), 500);
    }

    /**
     * Dashboard view
     */
    public function dashboard(): Response
    {
        return Inertia::render('Renewal/Dashboard', [
            'stats' => $this->getRenewalStats(),
        ]);
    }

    /**
     * Preview email for a renewal
     */
    public function previewEmail(Request $request, int $id)
    {
        $type = $request->input('type', 'first');
        
        $preview = $this->emailService->previewEmail($id, $type);
        
        if (isset($preview['error'])) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()
                    ->withErrors(['error' => $preview['error']]);
            }
            return response()->json(['error' => $preview['error']], 404);
        }
        
        return response()->json($preview);
    }

    /**
     * Get renewal statistics for dashboard
     */
    private function getRenewalStats(): array
    {
        $filters = new RenewalFilterDTO();
        
        // Get counts by step
        $stepCounts = [];
        for ($step = 0; $step <= 5; $step++) {
            $filters->step = $step;
            $query = $this->queryService->buildQuery($filters);
            $stepCounts["step_$step"] = $query->count();
        }
        
        // Get counts by invoice step
        $invoiceStepCounts = [];
        $filters->step = null;
        for ($invoiceStep = 0; $invoiceStep <= 3; $invoiceStep++) {
            $filters->invoiceStep = $invoiceStep;
            $query = $this->queryService->buildQuery($filters);
            $invoiceStepCounts["invoice_step_$invoiceStep"] = $query->count();
        }
        
        return [
            'by_step' => $stepCounts,
            'by_invoice_step' => $invoiceStepCounts,
            'total_active' => array_sum($stepCounts),
        ];
    }

    /**
     * Generate payment XML for selected renewals
     */
    private function generatePaymentXml(array $ids): string
    {
        $renewals = $this->renewalRepository->getGroupedByClient($ids);
        
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><payments></payments>');
        
        foreach ($renewals as $clientId => $clientRenewals) {
            $payment = $xml->addChild('payment');
            $payment->addChild('client_id', $clientId);
            
            $total = 0;
            foreach ($clientRenewals as $renewal) {
                $item = $payment->addChild('renewal');
                $item->addChild('id', $renewal->id);
                $item->addChild('caseref', $renewal->caseref);
                $item->addChild('detail', $renewal->detail);
                $item->addChild('due_date', $renewal->due_date);
                
                $feeDTO = $this->feeCalculator->calculate(\App\DataTransferObjects\Renewal\RenewalDTO::fromModel($renewal));
                $item->addChild('cost', $feeDTO->cost);
                $item->addChild('fee', $feeDTO->fee);
                $item->addChild('total', $feeDTO->total);
                
                $total += $feeDTO->total;
            }
            
            $payment->addChild('total_amount', $total);
        }
        
        return $xml->asXML();
    }
}