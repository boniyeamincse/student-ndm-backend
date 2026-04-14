<?php

namespace App\Notifications;

use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the applicant after a successful application submission.
 * Implements ShouldQueue — will use queue when QUEUE_CONNECTION is configured.
 * Falls back to sync dispatch when QUEUE_CONNECTION=sync (default).
 */
class ApplicationReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly MembershipApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Application Received — Student Movement NDM')
            ->greeting('Dear '.$this->application->full_name.',')
            ->line('Thank you for submitting your membership application to Student Movement NDM.')
            ->line('Your application has been received and is currently under review.')
            ->line('**Application No:** '.$this->application->application_no)
            ->line('**Status:** Pending')
            ->line('We will notify you once a decision has been made.')
            ->salutation('Student Movement NDM — Membership Team');
    }
}
