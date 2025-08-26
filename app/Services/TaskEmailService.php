<?php

namespace App\Services;

use App\Notifications\SystemTasksSummaryNotification;
use App\Models\Actor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class TaskEmailService
{
    /**
     * Send system-wide task summary to configured email addresses.
     * Uses Laravel's proper on-demand notifications with intelligent language detection.
     */
    public function sendSystemTasksSummary(Collection $tasks, ?string $language = null): void
    {
        $emailTo = config('tasks-email.email_to');
        $emailBcc = config('tasks-email.email_bcc');
        
        if (!$emailTo) {
            Log::info('No system summary email configured - skipping');
            return;
        }
        
        // Detect appropriate language if not specified
        if (!$language) {
            $language = $this->detectSystemLanguage($tasks, $emailTo);
        }
        
        $this->validateLanguage($language);
        
        try {
            $notification = new SystemTasksSummaryNotification($tasks, $language);

            Notification::route('mail', $emailTo)->notify($notification);
            
            Log::info('System task summary sent successfully', [
                'recipient' => $emailTo,
                'task_count' => $tasks->count(),
                'language' => $language,
                'detection_method' => $language ? 'specified' : 'auto-detected'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send system task summary', [
                'recipients' => ['to' => $emailTo, 'bcc' => $emailBcc],
                'task_count' => $tasks->count(),
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Try to send with fallback configuration if SMTP fails
            if ($this->shouldRetryWithFallback($e)) {
                $this->sendWithFallbackConfiguration($tasks, $language, $emailTo, $emailBcc);
            } else {
                throw $e; // Re-throw so command can handle it properly
            }
        }
    }
    
    /**
     * Check if system task summary email is configured.
     */
    public function isSystemEmailConfigured(): bool
    {
        return !empty(config('tasks-email.email_to'));
    }
    
    /**
     * Get configured system email recipients.
     */
    public function getSystemEmailRecipients(): array
    {
        return [
            'to' => config('tasks-email.email_to'),
            'bcc' => config('tasks-email.email_bcc')
        ];
    }
    
    /**
     * Detect the most appropriate language for system notifications.
     * Uses intelligent detection based on task context and system configuration.
     */
    private function detectSystemLanguage(Collection $tasks, string $primaryRecipient): string
    {
        // Try to detect language from responsible actors in the tasks
        if ($tasks->isNotEmpty()) {
            $responsibleActors = $tasks->map(function ($task) {
                return $task->matter->responsibleActor ?? null;
            })->filter()->unique('id');
            
            if ($responsibleActors->isNotEmpty()) {
                // Get the most common language among responsible actors
                $languages = $responsibleActors->map(function ($actor) {
                    return $actor->getLanguage();
                })->countBy();
                
                if ($languages->isNotEmpty()) {
                    $mostCommonLanguage = $languages->sortDesc()->keys()->first();
                    
                    Log::info('Detected language from responsible actors', [
                        'language' => $mostCommonLanguage,
                        'language_distribution' => $languages->toArray()
                    ]);
                    
                    return $mostCommonLanguage;
                }
            }
        }
        
        // Try to detect from email domain or configuration
        $domainLanguage = $this->detectLanguageFromEmail($primaryRecipient);
        if ($domainLanguage) {
            Log::info('Detected language from email domain', [
                'language' => $domainLanguage,
                'email' => $primaryRecipient
            ]);
            return $domainLanguage;
        }
        
        // Fallback to application default
        $fallbackLanguage = config('app.locale', 'en');
        Log::info('Using fallback language', [
            'language' => $fallbackLanguage,
            'reason' => 'no context available'
        ]);
        
        return $fallbackLanguage;
    }
    
    /**
     * Detect language from email address patterns.
     */
    private function detectLanguageFromEmail(string $email): ?string
    {
        // Common European domain patterns
        $domainPatterns = [
            '/\.fr$/' => 'fr',
            '/\.de$/' => 'de',
            '/\.at$/' => 'de',
            '/\.ch$/' => 'de', // Default to German for Switzerland
            '/\.be$/' => 'fr', // Default to French for Belgium
        ];
        
        $domain = substr(strrchr($email, '@'), 1);
        
        foreach ($domainPatterns as $pattern => $language) {
            if (preg_match($pattern, $domain)) {
                return $language;
            }
        }
        
        return null;
    }
    
    /**
     * Validate that the language is supported.
     */
    private function validateLanguage(string &$language): void
    {
        $supportedLanguages = ['en', 'fr', 'de'];
        
        if (!in_array($language, $supportedLanguages)) {
            Log::warning('Unsupported language detected, falling back to English', [
                'requested_language' => $language,
                'supported_languages' => $supportedLanguages
            ]);
            $language = 'en';
        }
    }
    
    /**
     * Determine if we should retry with fallback SMTP configuration.
     */
    private function shouldRetryWithFallback(\Exception $e): bool
    {
        $retryableErrors = [
            'Connection could not be established',
            'SMTP connect() failed',
            'Authentication failed',
            'Connection timed out'
        ];
        
        $errorMessage = $e->getMessage();
        
        foreach ($retryableErrors as $retryableError) {
            if (strpos($errorMessage, $retryableError) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Attempt to send notification with fallback SMTP configuration.
     */
    private function sendWithFallbackConfiguration(
        Collection $tasks, 
        string $language, 
        string $emailTo, 
        ?string $emailBcc
    ): void {
        Log::info('Attempting to send with fallback SMTP configuration');
        
        // Store original mail configuration
        $originalConfig = config('mail');
        
        try {
            // Try with a more basic SMTP configuration
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.timeout', 30);
            Config::set('mail.mailers.smtp.verify_peer', false);
            
            // Clear mail manager instance to pick up new config
            app()->forgetInstance('mail.manager');
            
            $notification = new SystemTasksSummaryNotification($tasks, $language);
            
            $tempNotifiable = new class($emailTo, $language) {
                private $email;
                private $language;
                
                public function __construct($email, $language) { 
                    $this->email = $email; 
                    $this->language = $language;
                }
                
                public function routeNotificationForMail() { 
                    return $this->email; 
                }
                
                public function preferredLocale() {
                    return $this->language;
                }
            };
            
            Notification::send($tempNotifiable, $notification);
                
            Log::info('System task summary sent successfully with fallback configuration', [
                'recipient' => $emailTo,
                'language' => $language
            ]);
            
        } catch (\Exception $fallbackException) {
            Log::error('Fallback SMTP configuration also failed', [
                'fallback_error' => $fallbackException->getMessage()
            ]);
            
            // Restore original configuration
            Config::set('mail', $originalConfig);
            app()->forgetInstance('mail.manager');
            
            throw $fallbackException;
        }
        
        // Restore original configuration
        Config::set('mail', $originalConfig);
        app()->forgetInstance('mail.manager');
    }
}