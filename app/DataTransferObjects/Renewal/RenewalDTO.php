<?php

namespace App\DataTransferObjects\Renewal;

use App\Models\Task;
use Carbon\Carbon;

class RenewalDTO
{
    public function __construct(
        public int $id,
        public int $matterId,
        public string $uid,
        public string $caseref,
        public string $country,
        public ?string $category,
        public int $detail,
        public Carbon $dueDate,
        public bool $done,
        public ?Carbon $doneDate,
        public float $cost,
        public float $fee,
        public int $step,
        public int $gracePeriod,
        public int $invoiceStep,
        public ?string $responsible,
        public ?string $clientName,
        public ?string $clientRef,
        public ?string $shortTitle,
        public ?string $title,
        public ?string $number,
        public ?string $eventName,
        public ?Carbon $eventDate,
        public ?string $language,
        public ?float $discount,
        public ?bool $smeStatus,
        public ?bool $tableFee,
        public ?float $costSupReduced,
        public ?float $costSup,
        public ?float $feeSupReduced,
        public ?float $feeSup,
        public ?float $costReduced,
        public ?float $feeReduced,
        public ?string $applicantName,
        public ?string $pubNum,
        public ?string $filNum,
        public ?string $origin,
        public ?int $clientId,
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            matterId: $model->matter_id,
            uid: $model->uid,
            caseref: $model->caseref,
            country: $model->country,
            category: $model->category,
            detail: (int) $model->detail,
            dueDate: Carbon::parse($model->due_date),
            done: (bool) $model->done,
            doneDate: $model->done_date ? Carbon::parse($model->done_date) : null,
            cost: (float) $model->cost,
            fee: (float) $model->fee,
            step: (int) $model->step,
            gracePeriod: (int) $model->grace_period,
            invoiceStep: (int) $model->invoice_step,
            responsible: $model->responsible,
            clientName: $model->client_name ?? null,
            clientRef: $model->client_ref ?? null,
            shortTitle: $model->short_title ?? null,
            title: $model->title ?? null,
            number: $model->number ?? null,
            eventName: $model->event_name ?? null,
            eventDate: $model->event_date ? Carbon::parse($model->event_date) : null,
            language: $model->language ?? 'fr',
            discount: $model->discount ?? null,
            smeStatus: $model->sme_status ?? null,
            tableFee: $model->table_fee ?? null,
            costSupReduced: $model->cost_sup_reduced ?? null,
            costSup: $model->cost_sup ?? null,
            feeSupReduced: $model->fee_sup_reduced ?? null,
            feeSup: $model->fee_sup ?? null,
            costReduced: $model->cost_reduced ?? null,
            feeReduced: $model->fee_reduced ?? null,
            applicantName: $model->applicant_name ?? null,
            pubNum: $model->pub_num ?? null,
            filNum: $model->fil_num ?? null,
            origin: $model->origin ?? null,
            clientId: $model->client_id ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'matter_id' => $this->matterId,
            'uid' => $this->uid,
            'caseref' => $this->caseref,
            'country' => $this->country,
            'category' => $this->category,
            'detail' => $this->detail,
            'due_date' => $this->dueDate->format('Y-m-d'),
            'done' => $this->done,
            'done_date' => $this->doneDate?->format('Y-m-d'),
            'cost' => $this->cost,
            'fee' => $this->fee,
            'step' => $this->step,
            'grace_period' => $this->gracePeriod,
            'invoice_step' => $this->invoiceStep,
            'responsible' => $this->responsible,
            'client_name' => $this->clientName,
            'client_ref' => $this->clientRef,
            'short_title' => $this->shortTitle,
            'title' => $this->title,
        ];
    }

    public function isInGracePeriod(): bool
    {
        return $this->gracePeriod > 0;
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function getAnnuity(): int
    {
        return $this->detail;
    }
}