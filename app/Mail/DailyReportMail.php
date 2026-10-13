<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;
    public string $date;
    public ?string $tehsilName;
    public string $pdfPath;
    public string $pdfFilename;

    public function __construct(array $payload, string $date, string $pdfPath, string $pdfFilename, ?string $tehsilName = null)
    {
        $this->payload = $payload;
        $this->date = $date;
        $this->pdfPath = $pdfPath;
        $this->pdfFilename = $pdfFilename;
        $this->tehsilName = $tehsilName;
    }

    public function envelope(): Envelope
    {
        $formattedDate = \Carbon\Carbon::parse($this->date)->format('D, d M Y');
        $subject = "PRSWSSP Daily Site Mobilization Report – {$formattedDate}";
        if ($this->tehsilName) {
            $subject .= " – Tehsil {$this->tehsilName}";
        }

        $senderEmail = Setting::get('smtp_from_address', config('mail.from.address', 'reports@prmsc.punjab.gov.pk'));
        $senderName = Setting::get('smtp_from_name', 'PRMSC Daily Reports');

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($senderEmail, $senderName),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily_report',
            with: [
                'payload' => $this->payload,
                'date' => $this->date,
                'tehsilName' => $this->tehsilName,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
