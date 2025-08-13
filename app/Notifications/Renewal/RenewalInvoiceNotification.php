<?php

namespace App\Notifications\Renewal;

use App\DataTransferObjects\Renewal\RenewalDTO;
use Illuminate\Notifications\Messages\MailMessage;

class RenewalInvoiceNotification extends BaseRenewalNotification
{
    protected RenewalDTO $renewal;
    protected array $clientData;
    protected string $invoiceNumber;
    protected string $subject;

    /**
     * Create a new notification instance.
     */
    public function __construct(RenewalDTO $renewal, array $clientData, string $invoiceNumber)
    {
        $this->renewal = $renewal;
        $this->clientData = $clientData;
        $this->invoiceNumber = $invoiceNumber;
        $this->language = app()->getLocale(); // Default to app locale for invoices
        $this->subject = 'Invoice for Patent Renewal - ' . $renewal->caseref;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject)
            ->view('email.renewalInvoice', [
                'renewal' => $this->renewal,
                'client' => $this->clientData,
                'invoice_number' => $this->invoiceNumber,
                'language' => $this->language,
                'subject' => $this->subject,
            ]);

        return $this->addEmailHeaders($message);
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return $this->getCommonDatabaseData('renewal_invoice', [
            'renewal_id' => $this->renewal->id,
            'renewal_caseref' => $this->renewal->caseref,
            'invoice_number' => $this->invoiceNumber,
            'client_name' => $this->clientData['name'],
            'client_email' => $this->clientData['invoice_email'] ?? $this->clientData['email'],
            'total_cost' => ($this->renewal->cost ?? 0) + ($this->renewal->fee ?? 0),
            'subject' => $this->subject,
        ]);
    }
}