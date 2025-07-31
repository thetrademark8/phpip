<?php

namespace App\Services\Renewal\Contracts;

use App\DataTransferObjects\Renewal\ActionResultDTO;

interface RenewalEmailServiceInterface
{
    /**
     * Send first call emails for renewals
     */
    public function sendFirstCall(array $ids, bool $preview = false): ActionResultDTO;

    /**
     * Send reminder emails for renewals
     */
    public function sendReminderCall(array $ids): ActionResultDTO;

    /**
     * Send last call emails for renewals
     */
    public function sendLastCall(array $ids): ActionResultDTO;

    /**
     * Send formal call emails for renewals
     */
    public function sendFormalCall(array $ids): ActionResultDTO;

    /**
     * Send invoice emails for renewals
     */
    public function sendInvoice(array $ids): ActionResultDTO;

    /**
     * Send report for renewals
     */
    public function sendReport(array $ids, string $recipient): ActionResultDTO;

    /**
     * Preview email for a renewal
     */
    public function previewEmail(int $id, string $type): array;
}