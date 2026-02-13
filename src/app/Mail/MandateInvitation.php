<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MandateInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];
        $supportEmail = $this->customer->tenantAccount?->customer_support_email;
        if ($supportEmail) {
            $accountName = $this->customer->tenantAccount->account_display_name ?? '';
            $replyTo[] = new Address($supportEmail, $accountName);
        }

        return new Envelope(
            subject: 'Direct Debit Mandate Authorization Required',
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mandate-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
