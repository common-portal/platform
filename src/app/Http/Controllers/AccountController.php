<?php

namespace App\Http\Controllers;

use App\Models\TenantAccount;
use App\Models\TenantAccountMembership;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\IbanAccount;
use App\Mail\MandateInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class AccountController extends Controller
{
    /**
     * Show create business account form.
     */
    public function showCreate()
    {
        return view('pages.account.create');
    }

    /**
     * Create a new business account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'account_display_name' => 'required|string|max:255',
            'primary_contact_email_address' => 'nullable|email|max:255',
        ]);

        DB::beginTransaction();

        try {
            $member = auth()->user();

            // Create business account
            // Use member name only if they have an actual name set (not email fallback)
            $memberName = trim("{$member->member_first_name} {$member->member_last_name}");
            
            $account = TenantAccount::create([
                'account_display_name' => $request->account_display_name,
                'account_type' => 'business_organization',
                'primary_contact_full_name' => $memberName ?: '',
                'primary_contact_email_address' => $request->primary_contact_email_address ?: $member->login_email_address,
            ]);

            // Create membership as owner
            TenantAccountMembership::create([
                'tenant_account_id' => $account->id,
                'platform_member_id' => $member->id,
                'account_membership_role' => 'account_owner',
                'granted_permission_slugs' => json_encode([
                    'can_access_account_settings',
                    'can_access_account_dashboard',
                    'can_manage_team_members',
                    'can_access_developer_tools',
                    'can_access_support_tickets',
                ]),
                'membership_status' => 'membership_active',
                'membership_accepted_at_timestamp' => now(),
            ]);

            DB::commit();

            // Switch to the new account
            session(['active_account_id' => $account->id]);
            \App\Models\MemberLastActiveAccount::remember($member->id, $account->id);

            return redirect()->route('account.settings')
                ->with('status', __translator('Business account created successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['account_display_name' => __translator('Failed to create account. Please try again.')]);
        }
    }

    /**
     * Show account settings.
     */
    public function showSettings()
    {
        $activeAccountId = session('active_account_id');
        $account = null;
        $membership = null;
        $isAdminImpersonating = session('admin_impersonating_from') !== null;

        if ($activeAccountId) {
            // If admin is impersonating, fetch account directly without membership check
            if ($isAdminImpersonating && auth()->user()->is_platform_administrator) {
                $account = TenantAccount::where('id', $activeAccountId)
                    ->where('is_soft_deleted', false)
                    ->first();
            } else {
                $account = auth()->user()->tenant_accounts()
                    ->where('tenant_accounts.id', $activeAccountId)
                    ->where('is_soft_deleted', false)
                    ->first();

                if ($account) {
                    $membership = auth()->user()->account_memberships()
                        ->where('tenant_account_id', $activeAccountId)
                        ->first();
                }
            }
        }

        // If primary_contact_full_name is empty, default to current member's name (not email)
        if ($account && empty($account->primary_contact_full_name)) {
            $member = auth()->user();
            $memberName = trim("{$member->member_first_name} {$member->member_last_name}");
            $account->primary_contact_full_name = $memberName ?: '';
        }

        return view('pages.account.settings', [
            'account' => $account,
            'membership' => $membership,
            'canEdit' => $isAdminImpersonating || ($membership && in_array($membership->account_membership_role, ['account_owner', 'account_administrator'])),
        ]);
    }

    /**
     * Update account settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'account_display_name' => 'required|string|max:255',
            'primary_contact_full_name' => 'nullable|string|max:255',
            'primary_contact_email_address' => 'nullable|email|max:255',
            'customer_support_email' => 'nullable|email|max:255',
            'whitelabel_subdomain_slug' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                'unique:tenant_accounts,whitelabel_subdomain_slug,' . session('active_account_id'),
                'not_in:www,api,admin,mail,smtp,ftp,app,portal,dashboard,login,auth',
            ],
        ]);

        $activeAccountId = session('active_account_id');
        $isAdminImpersonating = session('admin_impersonating_from') !== null;

        if (!$activeAccountId) {
            return back()->withErrors(['account' => __translator('No active account selected.')]);
        }

        // If admin is impersonating, fetch account directly
        if ($isAdminImpersonating && auth()->user()->is_platform_administrator) {
            $account = TenantAccount::where('id', $activeAccountId)
                ->where('is_soft_deleted', false)
                ->first();
        } else {
            $account = auth()->user()->tenant_accounts()
                ->where('tenant_accounts.id', $activeAccountId)
                ->first();
        }

        if (!$account) {
            return back()->withErrors(['account' => __translator('Account not found.')]);
        }

        // Check permission (admins impersonating always have permission)
        if (!$isAdminImpersonating) {
            $membership = auth()->user()->account_memberships()
                ->where('tenant_account_id', $activeAccountId)
                ->first();

            if (!$membership || !in_array($membership->account_membership_role, ['account_owner', 'account_administrator'])) {
                return back()->withErrors(['account' => __translator('You do not have permission to edit this account.')]);
            }
        }

        $updateData = [
            'account_display_name' => $request->account_display_name,
            'primary_contact_full_name' => $request->primary_contact_full_name,
            'primary_contact_email_address' => $request->primary_contact_email_address,
            'customer_support_email' => $request->customer_support_email,
        ];

        // Only business accounts can have subdomains
        if ($account->isBusinessAccount()) {
            $updateData['whitelabel_subdomain_slug'] = $request->whitelabel_subdomain_slug 
                ? strtolower($request->whitelabel_subdomain_slug) 
                : null;
        }

        $account->update($updateData);

        return back()->with('status', __translator('Account settings updated successfully!'));
    }

    /**
     * Soft delete a business account (Danger Zone).
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'confirm_delete' => 'required|in:DELETE',
        ]);

        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            return back()->withErrors(['account' => __translator('No active account selected.')]);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        if (!$account) {
            return back()->withErrors(['account' => __translator('Account not found.')]);
        }

        // Cannot delete personal accounts
        if ($account->account_type === 'personal_individual') {
            return back()->withErrors(['account' => __translator('Personal accounts cannot be deleted.')]);
        }

        // Check ownership
        $membership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$membership || $membership->account_membership_role !== 'account_owner') {
            return back()->withErrors(['account' => __translator('Only the account owner can delete this account.')]);
        }

        // Soft delete
        $account->update([
            'is_soft_deleted' => true,
            'soft_deleted_at_timestamp' => now(),
        ]);

        // Switch to personal account
        $personalAccount = auth()->user()->tenant_accounts()
            ->where('account_type', 'personal_individual')
            ->where('is_soft_deleted', false)
            ->wherePivot('membership_status', 'membership_active')
            ->first();

        if ($personalAccount) {
            session(['active_account_id' => $personalAccount->id]);
        } else {
            session()->forget('active_account_id');
        }

        return redirect()->route('home')
            ->with('status', __translator('Account deleted successfully.'));
    }

    /**
     * Upload account logo.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
        ]);

        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            return back()->withErrors(['account' => __translator('No active account selected.')]);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        if (!$account) {
            return back()->withErrors(['account' => __translator('Account not found.')]);
        }

        // Check permission
        $membership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$membership || !in_array($membership->account_membership_role, ['account_owner', 'account_administrator'])) {
            return back()->withErrors(['account' => __translator('You do not have permission to edit this account.')]);
        }

        $file = $request->file('logo');
        
        // Generate filename: original_accounthash_datetime.extension
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName); // Sanitize
        $accountHash = substr($account->record_unique_identifier, 0, 8);
        $datetime = now()->format('Ymd_His');
        
        // Always save as jpg for consistency and smaller size
        $filename = "{$originalName}_{$accountHash}_{$datetime}.jpg";
        
        // Resize image to logo size (256x256 max) using GD
        $resizedImage = $this->resizeLogo($file->getPathname(), 256);
        
        // Store in public/uploads/accounts/icons
        $storagePath = storage_path('app/public/uploads/accounts/icons');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        $fullPath = $storagePath . '/' . $filename;
        imagejpeg($resizedImage, $fullPath, 85); // 85% quality
        imagedestroy($resizedImage);
        
        $path = 'uploads/accounts/icons/' . $filename;
        
        // Delete old logo if exists
        if ($account->branding_logo_image_path) {
            Storage::disk('public')->delete($account->branding_logo_image_path);
        }
        
        // Update account
        $account->update([
            'branding_logo_image_path' => $path,
        ]);

        return back()->with('status', __translator('Account logo updated successfully.'));
    }
    
    /**
     * Resize image to logo size while maintaining aspect ratio.
     */
    private function resizeLogo(string $sourcePath, int $maxSize): \GdImage
    {
        $imageInfo = getimagesize($sourcePath);
        $mime = $imageInfo['mime'];
        
        // Create image resource based on type
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                $source = imagecreatefromjpeg($sourcePath);
        }
        
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);
        
        // Calculate new dimensions (fit within maxSize x maxSize)
        if ($origWidth > $origHeight) {
            $newWidth = min($origWidth, $maxSize);
            $newHeight = (int) ($origHeight * ($newWidth / $origWidth));
        } else {
            $newHeight = min($origHeight, $maxSize);
            $newWidth = (int) ($origWidth * ($newHeight / $origHeight));
        }
        
        // Create resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        // Resize
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagedestroy($source);
        
        return $resized;
    }

    /**
     * Remove account logo.
     */
    public function removeLogo()
    {
        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            return back()->withErrors(['account' => __translator('No active account selected.')]);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        if (!$account) {
            return back()->withErrors(['account' => __translator('Account not found.')]);
        }

        // Check permission
        $membership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$membership || !in_array($membership->account_membership_role, ['account_owner', 'account_administrator'])) {
            return back()->withErrors(['account' => __translator('You do not have permission to edit this account.')]);
        }

        if ($account->branding_logo_image_path) {
            Storage::disk('public')->delete($account->branding_logo_image_path);
            $account->update(['branding_logo_image_path' => null]);
        }

        return back()->with('status', __translator('Account logo removed.'));
    }

    /**
     * Show account dashboard with statistics.
     */
    public function dashboard()
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->with('error', 'No active account selected.');
        }

        // Get transaction count
        $transactionCount = Transaction::where('tenant_account_id', $activeAccountId)->count();
        
        // Get total transaction amount (sum of all received amounts)
        $transactionTotal = Transaction::where('tenant_account_id', $activeAccountId)
            ->sum('amount');

        return view('pages.account.dashboard', [
            'transactionCount' => $transactionCount,
            'transactionTotal' => $transactionTotal,
        ]);
    }

    /**
     * Show customers page with tabs.
     */
    public function customers()
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->with('error', 'No active account selected.');
        }

        $customers = Customer::forAccount($activeAccountId)
            ->with('settlementIban')
            ->orderBy('created_at_timestamp', 'desc')
            ->get();

        return view('pages.account.customers', [
            'customers' => $customers,
        ]);
    }

    /**
     * Send mandate invitation to customer.
     */
    public function sendMandateInvitation(Request $request)
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->with('error', 'No active account selected.');
        }

        $request->validate([
            'customer_full_name' => 'required|string|max:255',
            'customer_primary_contact_name' => 'nullable|string|max:255',
            'customer_primary_contact_email' => 'required|email|max:255',
            'recurring_frequency' => 'required|in:daily,weekly,monthly',
            'billing_amount' => 'required|numeric|min:0.01',
            'billing_currency' => 'required|string|max:3',
            'billing_dates' => 'required|array|min:1',
            'billing_start_date' => 'required_if:recurring_frequency,daily|nullable|date|after_or_equal:today',
            'billing_name_on_account' => 'nullable|string|max:255',
            'customer_iban' => 'nullable|string|max:34',
            'customer_bic' => 'nullable|string|max:11',
            'billing_bank_name' => 'nullable|string|max:255',
            'settlement_iban_hash' => 'nullable|string|max:32',
        ]);

        DB::beginTransaction();

        try {
            $customer = Customer::create([
                'tenant_account_id' => $activeAccountId,
                'customer_full_name' => $request->customer_full_name,
                'customer_primary_contact_name' => $request->customer_primary_contact_name,
                'customer_primary_contact_email' => $request->customer_primary_contact_email,
                'recurring_frequency' => $request->recurring_frequency,
                'billing_dates' => $request->billing_dates,
                'billing_start_date' => $request->billing_start_date,
                'billing_amount' => $request->billing_amount,
                'billing_currency' => $request->billing_currency,
                'settlement_iban_hash' => $request->settlement_iban_hash,
                'billing_name_on_account' => $request->billing_name_on_account,
                'customer_iban' => $request->customer_iban,
                'customer_bic' => $request->customer_bic,
                'billing_bank_name' => $request->billing_bank_name,
                'mandate_status' => 'invitation_pending',
                'mandate_active_or_paused' => 'paused',
                'invitation_sent_at' => now(),
            ]);

            Mail::to($customer->customer_primary_contact_email)->send(
                new MandateInvitation($customer)
            );

            DB::commit();

            return redirect()->route('account.customers')
                ->with('status', 'Mandate invitation sent successfully to ' . $customer->customer_primary_contact_email);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to send mandate invitation: ' . $e->getMessage()]);
        }
    }

    /**
     * Resend mandate authorization email to an existing customer.
     */
    public function resendMandateInvitation(Request $request, $customerId)
    {
        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            return redirect()->route('home')->with('error', 'No active account selected.');
        }

        $customer = Customer::where('id', $customerId)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        if ($customer->mandate_status !== 'invitation_pending') {
            return redirect()->route('account.customers')
                ->withErrors(['error' => 'This mandate has already been authorized and cannot be resent.']);
        }

        try {
            Mail::to($customer->customer_primary_contact_email)->send(
                new MandateInvitation($customer)
            );

            $customer->update(['invitation_sent_at' => now()]);

            return redirect()->route('account.customers')
                ->with('status', 'Mandate authorization resent successfully to ' . $customer->customer_primary_contact_email)
                ->with('expanded_customer_id', $customer->id);
        } catch (\Exception $e) {
            return redirect()->route('account.customers')
                ->withErrors(['error' => 'Failed to resend mandate: ' . $e->getMessage()]);
        }
    }

    /**
     * Update an existing customer's details.
     */
    public function updateCustomer(Request $request, $customer)
    {
        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->with('error', 'No active account selected.');
        }

        $customerRecord = Customer::where('id', $customer)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        $request->validate([
            'customer_full_name' => 'required|string|max:255',
            'customer_primary_contact_name' => 'nullable|string|max:255',
            'customer_primary_contact_email' => 'required|email|max:255',
            'recurring_frequency' => 'required|in:daily,weekly,monthly',
            'billing_amount' => 'required|numeric|min:0.01',
            'billing_currency' => 'required|string|max:3',
            'billing_dates' => 'required|array|min:1',
            'billing_start_date' => 'required_if:recurring_frequency,daily|nullable|date',
            'billing_name_on_account' => 'nullable|string|max:255',
            'customer_iban' => 'nullable|string|max:34',
            'customer_bic' => 'nullable|string|max:11',
            'billing_bank_name' => 'nullable|string|max:255',
            'settlement_iban_hash' => 'nullable|string|max:32',
        ]);

        // Detect which fields changed for the notification email
        $fieldLabels = [
            'customer_full_name' => 'Customer Name',
            'customer_primary_contact_name' => 'Contact Name',
            'customer_primary_contact_email' => 'Contact Email',
            'recurring_frequency' => 'Payment Frequency',
            'billing_amount' => 'Billing Amount',
            'billing_currency' => 'Billing Currency',
            'billing_name_on_account' => 'Name on Account',
            'customer_iban' => 'Account IBAN',
            'customer_bic' => 'Bank BIC',
            'billing_bank_name' => 'Bank Name',
        ];

        $changedFields = [];
        foreach ($fieldLabels as $field => $label) {
            $oldValue = $customerRecord->getOriginal($field);
            $newValue = $request->$field;
            if ((string) $oldValue !== (string) $newValue) {
                $changedFields[] = $label;
            }
        }
        // Check billing_dates separately (array comparison)
        if (json_encode($customerRecord->getOriginal('billing_dates')) !== json_encode($request->billing_dates)) {
            $changedFields[] = 'Billing Schedule';
        }

        $customerRecord->update([
            'customer_full_name' => $request->customer_full_name,
            'customer_primary_contact_name' => $request->customer_primary_contact_name,
            'customer_primary_contact_email' => $request->customer_primary_contact_email,
            'recurring_frequency' => $request->recurring_frequency,
            'billing_dates' => $request->billing_dates,
            'billing_start_date' => $request->billing_start_date,
            'billing_amount' => $request->billing_amount,
            'billing_currency' => $request->billing_currency,
            'settlement_iban_hash' => $request->settlement_iban_hash,
            'billing_name_on_account' => $request->billing_name_on_account,
            'customer_iban' => $request->customer_iban,
            'customer_bic' => $request->customer_bic,
            'billing_bank_name' => $request->billing_bank_name,
        ]);

        // Send update notice email to the customer
        if (!empty($changedFields)) {
            Mail::to($customerRecord->customer_primary_contact_email)->send(
                new \App\Mail\MandateUpdateNotice($customerRecord, $changedFields)
            );
        }

        return redirect()->route('account.customers')
            ->with('status', 'Customer details updated successfully for ' . $customerRecord->customer_full_name)
            ->with('expanded_customer_id', $customerRecord->id);
    }

    /**
     * Show transactions for the active account.
     */
    public function transactions()
    {
        $accountId = session('active_account_id');
        
        if (!$accountId) {
            return redirect()->route('dashboard')->with('error', 'No active account selected.');
        }

        $transactions = Transaction::where('tenant_account_id', $accountId)
            ->orderBy('datetime_updated', 'desc')
            ->paginate(20);

        return view('pages.account.transactions', compact('transactions'));
    }

    /**
     * Lookup bank name from BIC or IBAN using xAI API.
     */
    public function lookupBankFromBic(Request $request)
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

        \Log::info('Bank lookup request', ['type' => $type, 'value' => $value, 'api_key_set' => !empty($apiKey)]);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'xAI API key not configured',
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

            \Log::info('Bank lookup xAI response', [
                'type' => $type,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $bankName = trim($data['choices'][0]['message']['content'] ?? '');

                \Log::info('Bank lookup parsed result', ['type' => $type, 'bank_name' => $bankName]);

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
            \Log::error('Bank lookup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error looking up bank name',
            ], 500);
        }
    }

    /**
     * Toggle mandate active/paused state for a customer.
     */
    public function toggleMandateStatus(Request $request, Customer $customer)
    {
        $activeAccountId = session('active_account_id');

        if (!$activeAccountId || $customer->tenant_account_id != $activeAccountId) {
            return back()->withErrors(['error' => 'Unauthorized.']);
        }

        $request->validate([
            'mandate_active_or_paused' => 'required|in:active,paused',
        ]);

        $customer->update([
            'mandate_active_or_paused' => $request->mandate_active_or_paused,
        ]);

        $statusLabel = $request->mandate_active_or_paused === 'active' ? 'activated' : 'paused';

        return back()->with('status', "Mandate for {$customer->customer_full_name} has been {$statusLabel}.");
    }

    /**
     * Get IBANs filtered by currency for the active account.
     */
    public function ibansByCurrency(Request $request)
    {
        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            return response()->json(['success' => false, 'ibans' => []], 403);
        }

        $account = TenantAccount::find($activeAccountId);

        if (!$account) {
            return response()->json(['success' => false, 'ibans' => []], 404);
        }

        $currency = $request->query('currency');

        $query = IbanAccount::where('account_hash', $account->record_unique_identifier)
            ->active()
            ->whereNotNull('iban_ledger')
            ->where('iban_ledger', '!=', '');

        if ($currency) {
            $query->where('iban_currency_iso3', $currency);
        }

        $ibans = $query->orderBy('iban_friendly_name')->get()->map(function ($iban) {
            return [
                'hash' => $iban->record_unique_identifier,
                'friendly_name' => $iban->iban_friendly_name,
                'iban_number' => $iban->iban_number,
                'iban_ledger' => $iban->iban_ledger,
                'currency' => $iban->iban_currency_iso3,
            ];
        });

        return response()->json([
            'success' => true,
            'ibans' => $ibans,
        ]);
    }
}
