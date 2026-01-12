<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\PlatformSetting;
use App\Models\SupportTicketAttachment;

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
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip',
        ]);

        $supportEmail = PlatformSetting::getValue(
            'brand_support_email_address', 
            'common-portal@nsdb.com'
        );

        $platformName = PlatformSetting::getValue(
            'platform_display_name',
            'Common Portal'
        );

        // Handle file attachments for public submissions
        $attachmentPaths = [];
        $attachmentRecords = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachment = SupportTicketAttachment::storePublicFile($file);
                $attachmentRecords[] = $attachment;
                $attachmentPaths[] = storage_path('app/public/' . $attachment->file_path);
            }
        }

        // Send email with attachments
        Mail::send([], [], function ($mail) use ($request, $supportEmail, $platformName, $attachmentPaths) {
            $mail->to($supportEmail)
                 ->replyTo($request->from_email, $request->from_name)
                 ->subject("[{$platformName} Support] {$request->subject}")
                 ->text($this->buildEmailBody($request, count($attachmentPaths)));
            
            // Attach files to email
            foreach ($attachmentPaths as $path) {
                if (file_exists($path)) {
                    $mail->attach($path);
                }
            }
        });

        return redirect()->route('support')
            ->with('success', 'Your message has been sent successfully. We\'ll get back to you soon!');
    }

    /**
     * Build the email body text.
     */
    protected function buildEmailBody(Request $request, int $attachmentCount = 0): string
    {
        $attachmentInfo = $attachmentCount > 0 ? "\nAttachments: {$attachmentCount} file(s)" : '';
        
        return <<<EOT
Support Form Submission

From: {$request->from_name}
Email: {$request->from_email}
Subject: {$request->subject}{$attachmentInfo}

Message:
{$request->message}

---
Sent from the support form.
EOT;
    }
}
