<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandate Already Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8 text-center">
        
        <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-4">Mandate Already Processed</h1>
        
        <p class="text-lg text-gray-600 mb-8">
            This direct debit mandate has already been confirmed or processed.
        </p>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8 text-left">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Mandate Status</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Customer Name:</span>
                    <span class="text-gray-900 font-semibold">{{ $customer->customer_full_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Status:</span>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        @if($customer->mandate_status === 'mandate_confirmed') bg-green-100 text-green-800
                        @elseif($customer->mandate_status === 'mandate_active') bg-blue-100 text-blue-800
                        @elseif($customer->mandate_status === 'mandate_cancelled') bg-red-100 text-red-800
                        @endif">
                        {{ ucwords(str_replace('_', ' ', $customer->mandate_status)) }}
                    </span>
                </div>
                @if($customer->mandate_confirmed_at)
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Confirmed At:</span>
                    <span class="text-gray-900">{{ $customer->mandate_confirmed_at->format('Y-m-d H:i:s') }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">
                If you have questions about this mandate or need to make changes, please contact the merchant directly.
            </p>
        </div>

        <div class="text-sm text-gray-500">
            <p>Contact: {{ $customer->customer_primary_contact_email }}</p>
        </div>

    </div>

</body>
</html>
