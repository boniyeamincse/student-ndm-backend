<?php

namespace App\Notifications;

use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the applicant when their application is rejected.
 */
class ApplicationRejectedNotification extends Notification implements ShouldQueue
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
        $mail = (new MailMessage())
            ->subject('Membership Application Update — Student Movement NDM')
            ->greeting('Dear '.$this->application->full_name.',')
            ->line('Thank you for your interest in Student Movement NDM.')
            ->line('Unfortunately, after careful review, your membership application has **not been approved** at this time.');

        if ($this->application->rejection_reason) {
            $mail->line('**Reason:** '.$this->application->rejection_reason);
        }

        return $mail
            ->line('You are welcome to re-apply in the future once the issue has been addressed.')
            ->line('If you have questions, please contact our membership team.')
            ->salutation('Student Movement NDM — Membership Team');
    }
}
