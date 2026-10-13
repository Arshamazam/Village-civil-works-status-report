<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $pendingSites;
    public string $date;

    public function __construct(Collection $pendingSites, string $date)
    {
        $this->pendingSites = $pendingSites;
        $this->date = $date;
    }

    public function envelope(): Envelope
    {
        $count = $this->pendingSites->count();
        $senderEmail = Setting::get('smtp_from_address', config('mail.from.address', 'reports@prmsc.punjab.gov.pk'));
        $senderName = Setting::get('smtp_from_name', 'PRMSC Reporting Reminder');

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($senderEmail, $senderName),
            subject: "Pending Daily Return – {$count} Sites Requiring Submission",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
            with: [
                'pendingSites' => $this->pendingSites,
                'date' => $this->date,
            ]
        );
    }
}
