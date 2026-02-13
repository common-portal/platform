<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandate Confirmed Successfully</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8 text-center">
        
        <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-4">Mandate Confirmed Successfully!</h1>
        
        <p class="text-lg text-gray-600 mb-8">
            Your direct debit mandate has been successfully confirmed and activated.
        </p>

        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8 text-left">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Confirmation Details</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Customer Name:</span>
                    <span class="text-gray-900 font-semibold">{{ $customer->customer_full_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Email:</span>
                    <span class="text-gray-900">{{ $customer->customer_primary_contact_email }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Payment Amount:</span>
                    <span class="text-gray-900 font-semibold">{{ number_format($customer->billing_amount, 2) }} {{ $customer->billing_currency }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Frequency:</span>
                    <span class="text-gray-900">
                        @if($customer->recurring_frequency === 'weekly')
                            Once per Week
                        @elseif($customer->recurring_frequency === 'twice_monthly')
                            Twice per Month
                        @elseif($customer->recurring_frequency === 'monthly')
                            Once per Month
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">IBAN:</span>
                    <span class="text-gray-900 font-mono">{{ $customer->customer_iban }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Confirmed At:</span>
                    <span class="text-gray-900">{{ $customer->mandate_confirmed_at->format('Y-m-d H:i:s') }}</span>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">
                <strong>What's Next?</strong><br>
                Recurring payments will be processed automatically according to the schedule you've confirmed. 
                You will receive notifications before each payment is processed.
            </p>
        </div>

        <div class="text-sm text-gray-500 space-y-2">
            <p>You can revoke this mandate at any time by contacting the merchant.</p>
            <p>A confirmation email has been sent to {{ $customer->customer_primary_contact_email }}</p>
        </div>

    </div>

</body>
</html>
