<?php

namespace App\Services\Email;

use App\Models\Actor;
use App\Models\ClassifierType;
use App\Models\Matter;
use Illuminate\Support\Facades\Auth;

class PlaceholderService
{
    protected ?Matter $matter = null;

    protected ?Actor $recipient = null;

    protected ?array $classifierTypes = null;

    public function setMatter(Matter $matter): self
    {
        $this->matter = $matter;

        return $this;
    }

    public function setRecipient(?Actor $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Get all available placeholders with their descriptions.
     */
    public function getAvailablePlaceholders(): array
    {
        $matterPlaceholders = [
            '{{matter.reference}}' => __('email.placeholder.matter_reference'),
            '{{matter.title}}' => __('email.placeholder.matter_title'),
            '{{matter.filing_date}}' => __('email.placeholder.matter_filing_date'),
            '{{matter.filing_number}}' => __('email.placeholder.matter_filing_number'),
            '{{matter.priority_date}}' => __('email.placeholder.matter_priority_date'),
            '{{matter.registration_date}}' => __('email.placeholder.matter_registration_date'),
            '{{matter.registration_number}}' => __('email.placeholder.matter_registration_number'),
            '{{matter.publication_date}}' => __('email.placeholder.matter_publication_date'),
            '{{matter.opposition_deadline}}' => __('email.placeholder.matter_opposition_deadline'),
            '{{matter.priority_deadline}}' => __('email.placeholder.matter_priority_deadline'),
            '{{matter.expire_date}}' => __('email.placeholder.matter_expire_date'),
            '{{matter.country}}' => __('email.placeholder.matter_country'),
            '{{matter.category}}' => __('email.placeholder.matter_category'),
        ];

        // Add dynamic classifier placeholders
        foreach ($this->getClassifierTypes() as $type) {
            $code = strtolower($type->code);
            $matterPlaceholders["{{matter.classifier.{$code}}}"] = $type->type;
        }

        return [
            'matter' => $matterPlaceholders,
            'client' => [
                '{{client.name}}' => __('email.placeholder.client_name'),
                '{{client.email}}' => __('email.placeholder.client_email'),
                '{{client.address}}' => __('email.placeholder.client_address'),
                '{{client.company}}' => __('email.placeholder.client_company'),
                '{{client.ref}}' => __('email.placeholder.client_ref'),
            ],
            'actor' => [
                '{{actor.name}}' => __('email.placeholder.actor_name'),
                '{{actor.email}}' => __('email.placeholder.actor_email'),
                '{{actor.first_name}}' => __('email.placeholder.actor_first_name'),
            ],
            'owner' => [
                '{{owner.name}}' => __('email.placeholder.owner_name'),
                '{{owner.address}}' => __('email.placeholder.owner_address'),
            ],
            'agent' => [
                '{{agent.name}}' => __('email.placeholder.agent_name'),
                '{{agent.email}}' => __('email.placeholder.agent_email'),
                '{{agent.ref}}' => __('email.placeholder.agent_ref'),
            ],
            'user' => [
                '{{user.name}}' => __('email.placeholder.user_name'),
                '{{user.email}}' => __('email.placeholder.user_email'),
            ],
            'system' => [
                '{{today}}' => __('email.placeholder.today'),
                '{{app_name}}' => __('email.placeholder.app_name'),
                '{{app_url}}' => __('email.placeholder.app_url'),
            ],
        ];
    }

    /**
     * Resolve all placeholders in the content.
     */
    public function resolve(string $content): string
    {
        $placeholders = $this->collectPlaceholderValues();

        foreach ($placeholders as $key => $value) {
            $content = str_replace($key, $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Collect all placeholder values from the matter and related entities.
     */
    protected function collectPlaceholderValues(): array
    {
        $values = [];

        // Matter placeholders
        if ($this->matter) {
            $values['{{matter.reference}}'] = $this->matter->uid;
            $values['{{matter.title}}'] = $this->matter->titles->first()?->value;
            $values['{{matter.filing_date}}'] = $this->matter->filing?->event_date?->isoFormat('L');
            $values['{{matter.filing_number}}'] = $this->matter->filing?->detail;
            $values['{{matter.priority_date}}'] = $this->matter->priority()->first()?->event_date?->isoFormat('L');
            $values['{{matter.registration_date}}'] = ($this->matter->grant ?? $this->matter->registration)?->event_date?->isoFormat('L');
            $values['{{matter.registration_number}}'] = ($this->matter->grant ?? $this->matter->registration)?->detail;
            $values['{{matter.publication_date}}'] = $this->matter->publication?->event_date?->isoFormat('L');
            $values['{{matter.opposition_deadline}}'] = $this->getTaskDueDate('FOP');
            $values['{{matter.priority_deadline}}'] = $this->getTaskDueDate('PRID');
            $values['{{matter.expire_date}}'] = $this->matter->expire_date;
            $values['{{matter.country}}'] = $this->matter->countryInfo?->name;
            $values['{{matter.category}}'] = $this->matter->category?->category;

            // Dynamic classifier values
            $this->collectClassifierValues($values);

            // Client placeholders
            $client = $this->matter->clientFromLnk();
            $values['{{client.name}}'] = $client?->name;
            $values['{{client.email}}'] = $client?->actor?->email;
            $values['{{client.address}}'] = $client?->actor?->address;
            $values['{{client.company}}'] = $client?->actor?->company?->name;
            $values['{{client.ref}}'] = $client?->actor_ref;

            // Owner placeholders
            $values['{{owner.name}}'] = $this->matter->getOwnerName();
            $owner = $this->matter->owners()->first();
            $values['{{owner.address}}'] = $owner?->actor?->address;

            // Agent placeholders
            $agent = $this->matter->agent();
            $values['{{agent.name}}'] = $agent?->name;
            $values['{{agent.email}}'] = $agent?->actor?->email;
            $values['{{agent.ref}}'] = $agent?->actor_ref;
        }

        // Recipient/Actor placeholders
        $values['{{actor.name}}'] = $this->recipient?->name;
        $values['{{actor.email}}'] = $this->recipient?->email;
        $values['{{actor.first_name}}'] = $this->recipient?->first_name;

        // User placeholders
        $user = Auth::user();
        $values['{{user.name}}'] = $user?->name;
        $values['{{user.email}}'] = $user?->email;

        // System placeholders
        $values['{{today}}'] = now()->isoFormat('L');
        $values['{{app_name}}'] = config('app.name');
        $values['{{app_url}}'] = config('app.url');

        return $values;
    }

    /**
     * Get all classifier types (cached for the request).
     */
    protected function getClassifierTypes(): array
    {
        if ($this->classifierTypes === null) {
            $this->classifierTypes = ClassifierType::all()->all();
        }

        return $this->classifierTypes;
    }

    /**
     * Get the due date of a specific task for the current matter.
     */
    protected function getTaskDueDate(string $taskCode): ?string
    {
        if (! $this->matter) {
            return null;
        }

        $task = $this->matter->tasks()
            ->where('task.code', $taskCode)
            ->where('done', 0)
            ->orderBy('due_date')
            ->first();

        return $task?->due_date?->isoFormat('L');
    }

    /**
     * Collect classifier values for all classifier types.
     */
    protected function collectClassifierValues(array &$values): void
    {
        if (! $this->matter) {
            return;
        }

        // Get all classifiers for this matter (including inherited ones)
        $classifiers = $this->matter->classifiers->merge($this->matter->titles);

        // Group classifiers by type code
        $groupedClassifiers = $classifiers->groupBy('type_code');

        // Create a placeholder for each classifier type
        foreach ($this->getClassifierTypes() as $type) {
            $code = strtolower($type->code);
            $typeClassifiers = $groupedClassifiers->get($type->code, collect());

            if ($typeClassifiers->isNotEmpty()) {
                $values["{{matter.classifier.{$code}}}"] = $typeClassifiers->pluck('value')->filter()->implode(', ');
            } else {
                $values["{{matter.classifier.{$code}}}"] = '';
            }
        }
    }
}
