<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PublicMandateController extends Controller
{
    /**
     * Show the public mandate verification page.
     */
    public function show(string $customerHash)
    {
        $customer = Customer::where('record_unique_identifier', $customerHash)->firstOrFail();

        if ($customer->mandate_status !== 'invitation_pending') {
            return view('public.mandate-already-confirmed', [
                'customer' => $customer,
            ]);
        }

        return view('public.mandate-verification', [
            'customer' => $customer,
        ]);
    }

    /**
     * Handle IBAN/BIC submission and confirm mandate.
     */
    public function confirm(Request $request, string $customerHash)
    {
        $customer = Customer::where('record_unique_identifier', $customerHash)->firstOrFail();

        if ($customer->mandate_status !== 'invitation_pending') {
            return redirect()->route('public.mandate.show', $customerHash)
                ->withErrors(['error' => 'This mandate has already been processed.']);
        }

        $request->validate([
            'customer_iban' => 'required|string|max:34',
            'customer_bic' => 'required|string|max:11',
            'billing_bank_name' => 'nullable|string|max:255',
            'billing_name_on_account' => 'nullable|string|max:255',
        ]);

        $customer->update([
            'customer_iban' => strtoupper(str_replace(' ', '', $request->customer_iban)),
            'customer_bic' => strtoupper(str_replace(' ', '', $request->customer_bic)),
            'billing_bank_name' => $request->billing_bank_name,
            'billing_name_on_account' => $request->billing_name_on_account,
            'mandate_status' => 'mandate_confirmed',
            'mandate_active_or_paused' => 'active',
            'mandate_confirmed_at' => now(),
        ]);

        return view('public.mandate-success', [
            'customer' => $customer,
        ]);
    }

    /**
     * Public bank lookup from BIC or IBAN via xAI API.
     */
    public function lookupBank(Request $request)
    {
        $request->validate([
            'bic' => 'nullable|string|max:11',
            'iban' => 'nullable|string|max:34',
        ]);

        $bic = strtoupper(trim($request->bic ?? ''));
        $iban = strtoupper(trim($request->iban ?? ''));
        $type = $bic ? 'BIC' : ($iban ? 'IBAN' : null);
        $value = $bic ?: $iban;

        if (!$type || !$value) {
            return response()->json([
                'success' => false,
                'message' => 'A BIC or IBAN is required',
            ], 422);
        }

        $apiKey = config('services.xai.api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Bank lookup service not configured',
            ], 500);
        }

        $systemPrompt = $type === 'BIC'
            ? 'You are a SWIFT/BIC banking directory lookup tool. Given a BIC/SWIFT code, return ONLY the full official registered bank or financial institution name from the SWIFT directory. Return the institution name exactly as registered — not a brand name, subsidiary, or abbreviation. Do not include explanations, country info, or formatting. If unrecognized, respond with exactly: UNKNOWN'
            : 'You are a banking IBAN lookup tool. Given an IBAN, identify the bank from the embedded bank code portion of the IBAN. Return ONLY the full official registered bank or financial institution name. Return the institution name exactly as registered — not a brand name, subsidiary, or abbreviation. Do not include explanations, country info, or formatting. If unrecognized, respond with exactly: UNKNOWN';

        $userPrompt = $type === 'BIC'
            ? 'Look up the official registered bank name for BIC/SWIFT code: ' . $value
            : 'Look up the official registered bank name for IBAN: ' . $value . ' (identify the bank from the bank code portion of this IBAN)';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(config('services.xai.base_url') . '/chat/completions', [
                'model' => config('services.xai.model', 'grok-4-1-fast-reasoning'),
                'stream' => false,
                'temperature' => 0,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $bankName = trim($data['choices'][0]['message']['content'] ?? '');

                if ($bankName && $bankName !== 'UNKNOWN') {
                    return response()->json([
                        'success' => true,
                        'bank_name' => $bankName,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Could not identify bank from ' . $type . ' code',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'API request failed',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error looking up bank name',
            ], 500);
        }
    }
}
