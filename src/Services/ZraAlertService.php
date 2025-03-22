<?php

namespace Mak8Tech\ZraSmartInvoice\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mak8Tech\ZraSmartInvoice\Models\ZraTransactionLog;
use Mak8Tech\ZraSmartInvoice\Notifications\ZraFailureNotification;

class ZraAlertService
{
    /**
     * Send alerts for failed transactions
     *
     * @param int $threshold Number of failures that trigger an alert
     * @param int $period Period in minutes to check for failures
     * @return void
     */
    public function alertForFailures(int $threshold = 3, int $period = 60): void
    {
        // Get recent failures
        $recentFailures = ZraTransactionLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($period))
            ->get();

        // If we hit the threshold, send notification
        if ($recentFailures->count() >= $threshold) {
            $this->sendFailureAlert($recentFailures);
        }
    }

    /**
     * Send alerts for critical failures that require immediate attention
     *
     * @param ZraTransactionLog $log
     * @return void
     */
    public function alertForCriticalFailure(ZraTransactionLog $log): void
    {
        $this->sendFailureAlert(collect([$log]), true);
    }

    /**
     * Send failure alerts via configured channels
     *
     * @param \Illuminate\Support\Collection $failureLogs
     * @param bool $critical Whether this is a critical alert
     * @return void
     */
    protected function sendFailureAlert($failureLogs, bool $critical = false): void
    {
        $notifiables = $this->getNotifiables();
        
        if (empty($notifiables)) {
            // Log the alert if no notification channels are configured
            Log::error('ZRA Smart Invoice Failures Detected', [
                'logs' => $failureLogs->toArray(),
                'critical' => $critical,
                'no_notifiables' => true
            ]);
            return;
        }

        foreach ($notifiables as $notifiable) {
            Notification::send(
                $notifiable,
                new ZraFailureNotification($failureLogs, $critical)
            );
        }
    }

    /**
     * Get the entities to send notifications to
     *
     * @return array
     */
    protected function getNotifiables(): array
    {
        $notifiables = [];
        
        // Add email notifiables
        $emails = config('zra.alert_emails', []);
        foreach ($emails as $email) {
            $notifiables[] = (new \Illuminate\Notifications\AnonymousNotifiable)->route('mail', $email);
        }
        
        // Add Slack webhook if configured
        $slackWebhook = config('zra.slack_webhook_url');
        if ($slackWebhook) {
            $notifiables[] = (new \Illuminate\Notifications\AnonymousNotifiable)->route('slack', $slackWebhook);
        }
        
        // Add user model notifiables
        $userClass = config('zra.admin_user_class');
        $userIds = config('zra.admin_user_ids', []);
        
        if ($userClass && !empty($userIds)) {
            $users = $userClass::whereIn('id', $userIds)->get();
            $notifiables = array_merge($notifiables, $users->all());
        }
        
        return $notifiables;
    }
}
