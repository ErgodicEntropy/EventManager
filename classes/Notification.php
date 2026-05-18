<?php
declare(strict_types=1);

namespace Eventify\Notification;

use DateTime;
use InvalidArgumentException;

/**
 * Value Object representing the target of a notification.
 * Encapsulates recipient metadata to keep the Notifier class clean.
 */
class Recipient 
{
    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private string $phoneNumber,
        private array $preferences = ['email' => true, 'sms' => false, 'in_app' => true]
    ) {}

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPhoneNumber(): string { return $this->phoneNumber; }
    public function wantsChannel(string $channel): bool {
        return $this->preferences[$channel] ?? false;
    }
}

/**
 * Core Notification Engine (Pure OOP Object Domain)
 */
// Notification service which handles sending notificaitons to users via email, SMS msgs, or in-app alerts.

class Notifier 
{ 
    private DateTime $createdAt; 
    private string $compiledBody = ''; 
    private string $subject = '';
    private array $deliveryLog = [];
    
    public $time; 
    public string $notification; //Represents messages sent to users (event reminders, confirmations, cancellations).
    public string $template; //specifies the format of the notification message (welcome email, ticket confirmation, etc.).

    // Core template dictionary structure
    private array $templates = [
        'event_confirmation' => [
            'subject' => "Ticket Confirmed: {event_title}",
            'body'    => "Hi {name},\n\nYour spot at '{event_title}' has been successfully reserved!\nDate: {start_date}\nLocation: {location}\n\nBest,\nThe Team"
        ],
        'event_reminder' => [
            'subject' => "Happening Soon: {event_title}",
            'body'    => "Hey {name},\n\nThis is a quick reminder that '{event_title}' kicks off on {start_date}. We look forward to seeing you there!\n\nAccess Details: {location}"
        ],
        'event_cancellation' => [
            'subject' => "Cancelled: {event_title}",
            'body'    => "Important Notice: '{event_title}' has been cancelled by the organizer. If this was a paid registration, your refund is being processed automatically."
        ]
    ];

    /**
     * Engine Constructor
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    /**
     * Injects custom layout variations or overrides dynamically from other parts of the system.
     */
    public function registerTemplate(string $key, string $subject, string $body): self
    {
        if (empty($key) || empty($subject) || empty($body)) {
            throw new InvalidArgumentException("Template components cannot be blank.");
        }
        $this->templates[$key] = ['subject' => $subject, 'body' => $body];
        return $this;
    }

    /**
     * Compiles a specific template payload, swapping formatting wildcards with actual string variables.
     * Implements Fluent Interface pattern (returns $this).
     */
    public function build(string $templateKey, array $templateData, Recipient $recipient): self
    {
        if (!isset($this->templates[$templateKey])) {
            throw new InvalidArgumentException("Requested layout pattern '{$templateKey}' is unrecognized.");
        }

        // Auto-inject common recipient information so it doesn't need manual mapping passing arrays every time
        $templateData['name'] = $recipient->getName();

        $target = $this->templates[$templateKey];
        $this->subject      = $target['subject'];
        $this->compiledBody = $target['body'];

        // Token parsing loops
        foreach ($templateData as $token => $replacement) {
            $searchToken = "{" . $token . "}";
            $this->subject      = str_replace($searchToken, (string)$replacement, $this->subject);
            $this->compiledBody = str_replace($searchToken, (string)$replacement, $this->compiledBody);
        }

        return $this;
    }

    /**
     * Iterates over a user preferences payload matrix map to push outputs safely across 
     * matching communication subchannels.
     */
    public function send(Recipient $recipient): array
    {
        if (empty($this->compiledBody)) {
            throw new \RuntimeException("Cannot dispatch empty payload. Execute build() step first.");
        }

        // Check channel states cleanly against user settings object model properties
        if ($recipient->wantsChannel('email')) {
            $this->dispatchEmail($recipient->getEmail());
        }
        
        if ($recipient->wantsChannel('sms')) {
            $this->dispatchSMS($recipient->getPhoneNumber());
        }

        if ($recipient->wantsChannel('in_app')) {
            $this->dispatchInAppAlert($recipient->getId());
        }

        return $this->getDeliverySummary();
    }

    /**
     * Isolated Mock Transmission Layer: Emails
     */
    private function dispatchEmail(string $email): void
    {
        // Business logic execution layer (e.g., mailer service interfaces go here)
        $this->logDelivery('email', "Successfully sent to <{$email}>. Subject: {$this->subject}");
    }

    /**
     * Isolated Mock Transmission Layer: Cellular Carrier SMS
     */
    private function dispatchSMS(string $phone): void
    {
        // Business logic execution layer (e.g., SMS adapter payloads go here)
        $shortText = substr(strip_tags($this->compiledBody), 0, 160);
        $this->logDelivery('sms', "Dispatched textual SMS string segment to [{$phone}]: '{$shortText}'");
    }

    /**
     * Isolated Mock Transmission Layer: In-App System Feed Entity state entries
     */
    private function dispatchInAppAlert(int $userId): void
    {
        // Business logic execution layer (e.g., adding notification instances to a user's collection model)
        $this->logDelivery('in_app', "Appended internal alert to user timeline array index #{$userId}.");
    }

    /**
     * Internal status ledger utility update
     */
    private function logDelivery(string $type, string $statusText): void
    {
        $this->deliveryLog[] = [
            'type'      => $type,
            'timestamp' => new DateTime(),
            'log'       => $statusText
        ];
    }

    /**
     * Read-only tracking accessor function
     */
    public function getDeliverySummary(): array
    {
        return $this->deliveryLog;
    }

    /**
     * Returns compiled message subject line
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Returns compiled message core string text block
     */
    public function getCompiledBody(): string
    {
        return $this->compiledBody;
    }
}