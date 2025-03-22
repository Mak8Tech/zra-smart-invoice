<?php

namespace Mak8Tech\ZraSmartInvoice\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ZraFailureNotification extends Notification
{
    use Queueable;

    /**
     * @var Collection
     */
    protected $failures;

    /**
     * @var bool
     */
    protected $critical;

    /**
     * Create a new notification instance.
     *
     * @param Collection $failures
     * @param bool $critical
     * @return void
     */
    public function __construct(Collection $failures, bool $critical = false)
    {
        $this->failures = $failures;
        $this->critical = $critical;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = [];

        if (method_exists($notifiable, 'routeNotificationForMail')) {
            $channels[] = 'mail';
        }

        if (method_exists($notifiable, 'routeNotificationForSlack')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = $this->critical
            ? '🚨 CRITICAL ALERT: ZRA Smart Invoice Failures Detected'
            : '⚠️ ALERT: ZRA Smart Invoice Failures Detected';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Alert from ZRA Smart Invoice')
            ->line($this->critical
                ? 'Critical failures have been detected in your ZRA Smart Invoice integration that require immediate attention.'
                : 'Multiple failures have been detected in your ZRA Smart Invoice integration.');

        // Add failure details
        foreach ($this->failures as $index => $failure) {
            $mail->line("Failure #" . ($index + 1) . ":");
            $mail->line("Type: " . $failure->transaction_type);
            $mail->line("Reference: " . $failure->reference);
            $mail->line("Error: " . $failure->error_message);
            $mail->line("Time: " . $failure->created_at->format('Y-m-d H:i:s'));
            $mail->line('---');
        }

        $mail->action('View Transaction Logs', route('zra.logs'))
            ->line('Please review these failures and take appropriate action.');

        return $mail;
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        $slack = (new SlackMessage)
            ->from('ZRA Smart Invoice')
            ->error()
            ->content($this->critical
                ? '🚨 *CRITICAL ALERT*: ZRA Smart Invoice Failures Detected'
                : '⚠️ *ALERT*: ZRA Smart Invoice Failures Detected');

        // Add failure details
        $fields = [];
        foreach ($this->failures as $index => $failure) {
            $fields['Failure #' . ($index + 1)] = $failure->transaction_type . ' - ' . $failure->reference;
            $fields['Error'] = $failure->error_message;
            $fields['Time'] = $failure->created_at->format('Y-m-d H:i:s');
        }

        $slack->attachment(function ($attachment) use ($fields) {
            $attachment->title('Failure Details')
                ->fields($fields)
                ->action('View Logs', route('zra.logs'));
        });

        return $slack;
    }
}
