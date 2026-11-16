<?php

namespace App\Mail;

use App\Models\Student;
use App\Models\StudentCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentChargeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly StudentCharge $charge,
        public readonly Student $student,
        public readonly string $portalUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cobrança pendente - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-charge',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
