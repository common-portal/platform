<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\PlatformSetting;

class SupportController extends Controller
{
    /**
     * Show the support form page.
     */
    public function index()
    {
        return view('pages.support');
    }

    /**
     * Handle support form submission.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:10000',
        ]);

        $supportEmail = PlatformSetting::getValue(
            'brand_support_email_address', 
            'common-portal@nsdb.com'
        );

        $platformName = PlatformSetting::getValue(
            'platform_display_name',
            'Common Portal'
        );

        // Send email
        Mail::send([], [], function ($mail) use ($request, $supportEmail, $platformName) {
            $mail->to($supportEmail)
                 ->replyTo($request->from_email, $request->from_name)
                 ->subject("[{$platformName} Support] {$request->subject}")
                 ->text($this->buildEmailBody($request));
        });

        return redirect()->route('support')
            ->with('success', 'Your message has been sent successfully. We\'ll get back to you soon!');
    }

    /**
     * Build the email body text.
     */
    protected function buildEmailBody(Request $request): string
    {
        return <<<EOT
Support Form Submission

From: {$request->from_name}
Email: {$request->from_email}
Subject: {$request->subject}

Message:
{$request->message}

---
Sent from the support form.
EOT;
    }
}
