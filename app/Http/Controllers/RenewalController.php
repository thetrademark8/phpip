<?php

namespace App\Http\Controllers;

use App\Contracts\Renewal\RenewalEmailServiceInterface;
use App\Contracts\Renewal\RenewalExportServiceInterface;
use App\Contracts\Renewal\RenewalFeeCalculatorInterface;
use App\Contracts\Renewal\RenewalInvoiceServiceInterface;
use App\Contracts\Renewal\RenewalLogServiceInterface;
use App\Contracts\Renewal\RenewalQueryServiceInterface;
use App\Contracts\Renewal\RenewalWorkflowServiceInterface;
use App\DataTransferObjects\Renewal\RenewalFilterDTO;
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
        private RenewalInvoiceServiceInterface $invoiceService,
        private RenewalExportServiceInterface $exportService,
        private RenewalLogServiceInterface $logService,
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
        
        // Calculate fees in batch to avoid N+1 queries
        $fees = $this->feeCalculator->calculateBatch($renewals->getCollection());
        
        // Apply calculated fees to renewals
        $renewals->transform(function ($renewal) use ($fees) {
            if (isset($fees[$renewal->id])) {
                $renewal->cost = $fees[$renewal->id]->cost;
                $renewal->fee = $fees[$renewal->id]->fee;
            }
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
            return to_route('renewal.index')
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        // Send or preview first calls
        $result = $this->emailService->sendFirstCall($ids, $send !== 1);
        
        if ($result->success) {
            // Update step to 2 (reminder) if sent
            if ($send == 1) {
                $this->workflowService->updateStep($ids, 2);
            }
            
            return to_route('renewal.index', ['step' => 2])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Send reminder call for selected renewals
     */
    public function remindercall(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 2])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->emailService->sendReminderCall($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 2])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['step' => 2])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Send last call for selected renewals
     */
    public function lastcall(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 2])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->emailService->sendLastCall($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 2])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['step' => 2])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Send formal call for selected renewals
     */
    public function formalcall(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 2])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->emailService->sendFormalCall($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 2])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['step' => 2])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Generate XML file for selected renewals (payment)
     */
    public function payment(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 2])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        // Update invoice step to 1 (invoiced)
        $result = $this->workflowService->updateInvoiceStep($ids, 1);
        
        if ($result->success) {
            // Generate XML file for payment
            $xmlContent = $this->exportService->generatePaymentXml($ids);
            
            return response($xmlContent, 200)
                ->header('Content-Type', 'text/xml')
                ->header('Content-Disposition', 'attachment; filename="payment_' . date('YmdHis') . '.xml"');
        }
        
        return to_route('renewal.index', ['step' => 2])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Mark renewals as payment order received
     */
    public function topay(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 2])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->markAsPaymentOrderReceived($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 4])
                ->with('success', 'Marked as to pay');
        }
        
        return to_route('renewal.index', ['step' => 2])
            ->withErrors(['error' => $result->error]);
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
            return to_route('renewal.index')
                ->with('success', 'Fees updated successfully');
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => 'Failed to update fees']);
    }

    /**
     * Clear selected renewals
     */
    public function clear(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 2])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->abandon($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 2])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['step' => 2])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Generate invoice for selected renewals
     */
    public function invoice(Request $request, int $toinvoice)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 3])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->invoiceService->createInvoices($ids, $toinvoice === 1);
        
        if ($result->success) {
            return to_route('renewal.index', ['invoice_step' => 2])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['step' => 3])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Register receipt for selected renewals
     */
    public function receipt(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['invoice_step' => 2])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        // Update invoice step to 3 (paid)
        $result = $this->workflowService->updateInvoiceStep($ids, 3);
        
        if ($result->success) {
            return to_route('renewal.index', ['invoice_step' => 3])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['invoice_step' => 2])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Register receipts for selected renewals
     */
    public function receipts(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 4])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        // Update step to 5 (receipts)
        $result = $this->workflowService->updateStep($ids, 5);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 5])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['step' => 4])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Close selected renewals
     */
    public function closing(Request $request)
    {
        $ids = $request->input('task_ids', []);
        $done_date = $request->input('done_date');
        
        if (empty($ids)) {
            return to_route('renewal.index', ['step' => 5])
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->markAsDone($ids, $done_date);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 10])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index', ['step' => 5])
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Dashboard view
     */
    public function dashboard(): Response
    {
        return Inertia::render('Renewal/Dashboard', [
            'stats' => $this->queryService->getRenewalStats(),
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
            return response()->json(['error' => $preview['error']], 404);
        }
        
        return response()->json($preview);
    }



    /**
     * Create renewal orders for selected renewals
     */
    public function renewalOrder(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index')
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->createOrders($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 4])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Mark renewals as invoiced
     */
    public function renewalsInvoiced(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index')
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->markInvoiced($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['invoice_step' => 2])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Mark renewals as paid
     */
    public function paid(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index')
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->markPaid($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['invoice_step' => 3])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Mark renewals as done
     */
    public function done(Request $request)
    {
        $ids = $request->input('task_ids', []);
        $doneDate = $request->input('done_date');
        
        if (empty($ids)) {
            return to_route('renewal.index')
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->markAsDone($ids, $doneDate);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 10])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Mark renewals as abandoned
     */
    public function abandon(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index')
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->abandon($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 11])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Mark renewals as lapsing
     */
    public function lapsing(Request $request)
    {
        $ids = $request->input('task_ids', []);
        
        if (empty($ids)) {
            return to_route('renewal.index')
                ->withErrors(['error' => 'No renewals selected']);
        }
        
        $result = $this->workflowService->markAsLapsing($ids);
        
        if ($result->success) {
            return to_route('renewal.index', ['step' => 11])
                ->with('success', $result->message);
        }
        
        return to_route('renewal.index')
            ->withErrors(['error' => $result->error]);
    }

    /**
     * Export renewals to CSV
     */
    public function export(Request $request)
    {
        // Build filters from request
        $filters = RenewalFilterDTO::fromRequest($request);
        
        // Get renewals for export (typically invoice_step = 1)
        $filters->invoiceStep = $filters->invoiceStep ?? 1;
        
        // Build query and get renewals
        $query = $this->queryService->buildQuery($filters);
        $renewals = $query->get();
        
        // Export to CSV
        return $this->exportService->exportToCsv($renewals);
    }

    /**
     * Display renewal logs
     */
    public function logs(Request $request): Response
    {
        $filters = $request->except(['page', 'per_page']);
        $perPage = $request->input('per_page', 25);
        
        $logs = $this->logService->getLogs($filters, $perPage);
        
        return Inertia::render('Renewal/Logs', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}