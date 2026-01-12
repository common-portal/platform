<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public string $memberName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Support Ticket: ' . $this->ticket->ticket_subject_line,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-ticket-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
