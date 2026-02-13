<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Debit Mandate Authorization</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="background-color: #f8f9fa; padding: 30px; border-radius: 8px; margin-bottom: 30px;">
        <h1 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 24px;">Direct Debit Mandate Authorization</h1>
        <p style="color: #7f8c8d; margin: 0; font-size: 14px;">Action Required</p>
    </div>

    <div style="margin-bottom: 30px;">
        <p>Dear {{ $customer->customer_primary_contact_name ?: $customer->customer_full_name }},</p>
        
        <p>You have been invited to set up a recurring direct debit payment mandate for <strong>{{ $customer->customer_full_name }}</strong>.</p>
        
        @if($customer->customer_iban && $customer->customer_bic)
        <p>To authorize recurring payments, please review the payment details below and confirm the mandate:</p>
        @else
        <p>To authorize recurring payments, please review the payment details below and provide your IBAN and BIC information:</p>
        @endif
    </div>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h2 style="color: #2c3e50; font-size: 18px; margin: 0 0 15px 0;">Payment Details</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #7f8c8d; font-weight: 600;">Payment Frequency:</td>
                <td style="padding: 8px 0;">
                    @if($customer->recurring_frequency === 'weekly')
                        Once per Week
                    @elseif($customer->recurring_frequency === 'twice_monthly')
                        Twice per Month
                    @elseif($customer->recurring_frequency === 'monthly')
                        Once per Month
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #7f8c8d; font-weight: 600;">Payment Amount:</td>
                <td style="padding: 8px 0;">{{ number_format($customer->billing_amount, 2) }} {{ $customer->billing_currency }}</td>
            </tr>
            @if($customer->recurring_frequency === 'daily')
            <tr>
                <td style="padding: 8px 0; color: #7f8c8d; font-weight: 600;">Start Date:</td>
                <td style="padding: 8px 0;">{{ $customer->billing_start_date ? $customer->billing_start_date->format('M d, Y') : 'Not specified' }}</td>
            </tr>
            @elseif($customer->recurring_frequency === 'weekly')
            <tr>
                <td style="padding: 8px 0; color: #7f8c8d; font-weight: 600; vertical-align: top;">Billing Day(s):</td>
                <td style="padding: 8px 0;">
                    @php
                        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        $selectedDays = collect($customer->billing_dates ?? [])
                            ->filter(fn($d) => is_numeric($d))
                            ->map(fn($d) => $dayNames[(int)$d] ?? null)
                            ->filter()
                            ->values();
                    @endphp
                    {{ $selectedDays->isNotEmpty() ? $selectedDays->join(', ') : 'Not specified' }}
                </td>
            </tr>
            @elseif($customer->recurring_frequency === 'monthly')
            <tr>
                <td style="padding: 8px 0; color: #7f8c8d; font-weight: 600; vertical-align: top;">Billing Date(s):</td>
                <td style="padding: 8px 0;">
                    @php
                        $dates = collect($customer->billing_dates ?? [])
                            ->filter(fn($d) => is_numeric($d))
                            ->map(fn($d) => 'Day ' . $d)
                            ->values();
                    @endphp
                    {{ $dates->isNotEmpty() ? $dates->join(', ') : 'Not specified' }}
                </td>
            </tr>
            @endif
        </table>
    </div>

    <div style="text-align: center; margin: 40px 0;">
        <a href="{{ $customer->mandate_verification_url }}" 
           style="display: inline-block; background-color: #3498db; color: white; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
            Confirm Mandate Authorization
        </a>
    </div>

    @if(!$customer->customer_iban || !$customer->customer_bic)
    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 30px;">
        <p style="margin: 0; color: #856404;">
            <strong>Important:</strong> You will need to provide your IBAN and BIC details to complete the authorization. Please have this information ready.
        </p>
    </div>
    @endif

    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e9ecef; font-size: 14px; color: #7f8c8d;">
        <p>If you did not request this mandate or believe you received this email in error, please disregard this message or contact us for assistance.</p>
        
        <p style="margin-top: 20px;">
            <strong>Customer Information:</strong><br>
            Name: {{ $customer->customer_full_name }}<br>
            Email: {{ $customer->customer_primary_contact_email }}
        </p>
    </div>

</body>
</html>
