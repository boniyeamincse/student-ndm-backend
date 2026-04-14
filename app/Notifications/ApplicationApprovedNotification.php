<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the applicant when their application is approved and membership is granted.
 */
class ApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MembershipApplication $application,
        public readonly Member $member,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Membership Approved — Student Movement NDM')
            ->greeting('Dear '.$this->application->full_name.',')
            ->line('Congratulations! Your membership application has been **approved**.')
            ->line('**Application No:** '.$this->application->application_no)
            ->line('**Member No:** '.$this->member->member_no)
            ->line('You are now an official member of Student Movement NDM.')
            ->line(
                'A password setup link has been sent separately so you can access your member account. '.
                'Please check your email and set your password within 72 hours.'
            )
            ->line('Welcome to the movement!')
            ->salutation('Student Movement NDM — Membership Team');
    }
}
