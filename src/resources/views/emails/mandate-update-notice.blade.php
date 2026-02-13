<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandate Update Notice</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="background-color: #f8f9fa; padding: 30px; border-radius: 8px; margin-bottom: 30px;">
        <h1 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 24px;">Mandate Update Notice</h1>
        <p style="color: #7f8c8d; margin: 0; font-size: 14px;">For Your Records — No Action Required</p>
    </div>

    <div style="margin-bottom: 30px;">
        <p>Dear {{ $customer->customer_primary_contact_name ?: $customer->customer_full_name }},</p>
        
        <p>This is to inform you that details on your direct debit mandate for <strong>{{ $customer->customer_full_name }}</strong> have been updated.</p>

        <p>Please review the updated details below for your records. <strong>No action is required on your part.</strong></p>
    </div>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h2 style="color: #2c3e50; font-size: 18px; margin: 0 0 15px 0;">Updated Payment Details</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #7f8c8d; font-weight: 600;">Customer Name:</td>
                <td style="padding: 8px 0;">{{ $customer->customer_full_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #7f8c8d; font-weight: 600;">Payment Frequency:</td>
                <td style="padding: 8px 0;">
                    @if($customer->recurring_frequency === 'daily')
                        Daily
                    @elseif($customer->recurring_frequency === 'weekly')
                        Once per Week
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

    @if(!empty($changedFields))
    <div style="background-color: #e8f4fd; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 30px;">
        <p style="margin: 0 0 8px 0; font-weight: 600; color: #2c3e50;">What Changed:</p>
        <ul style="margin: 0; padding-left: 20px; color: #555;">
            @foreach($changedFields as $field)
                <li>{{ $field }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div style="margin-bottom: 30px;">
        <p>If you have any questions about these changes, or if you did not expect this update, please contact our support team:</p>
    </div>

    <div style="text-align: center; margin: 40px 0;">
        <a href="{{ url('/support?customer=' . $customer->record_unique_identifier) }}" 
           style="display: inline-block; background-color: #3498db; color: white; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
            Contact Support
        </a>
    </div>

    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e9ecef; font-size: 14px; color: #7f8c8d;">
        <p>This is an automated notification. No action is required on your part unless you have questions about the changes described above.</p>
        
        <p style="margin-top: 20px;">
            <strong>Customer Information:</strong><br>
            Name: {{ $customer->customer_full_name }}<br>
            Email: {{ $customer->customer_primary_contact_email }}
        </p>
    </div>

</body>
</html>
