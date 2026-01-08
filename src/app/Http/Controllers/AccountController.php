<?php

namespace App\Http\Controllers;

use App\Models\TenantAccount;
use App\Models\TenantAccountMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            $account = TenantAccount::create([
                'account_display_name' => $request->account_display_name,
                'account_type' => 'business_organization',
                'primary_contact_full_name' => $member->full_name,
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

            return redirect()->route('account.settings')
                ->with('status', 'Business account created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['account_display_name' => 'Failed to create account. Please try again.']);
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

        if ($activeAccountId) {
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

        return view('pages.account.settings', [
            'account' => $account,
            'membership' => $membership,
            'canEdit' => $membership && in_array($membership->account_membership_role, ['account_owner', 'account_administrator']),
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

        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        if (!$account) {
            return back()->withErrors(['account' => 'Account not found.']);
        }

        // Check permission
        $membership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$membership || !in_array($membership->account_membership_role, ['account_owner', 'account_administrator'])) {
            return back()->withErrors(['account' => 'You do not have permission to edit this account.']);
        }

        $updateData = [
            'account_display_name' => $request->account_display_name,
            'primary_contact_full_name' => $request->primary_contact_full_name,
            'primary_contact_email_address' => $request->primary_contact_email_address,
        ];

        // Only business accounts can have subdomains
        if ($account->isBusinessAccount()) {
            $updateData['whitelabel_subdomain_slug'] = $request->whitelabel_subdomain_slug 
                ? strtolower($request->whitelabel_subdomain_slug) 
                : null;
        }

        $account->update($updateData);

        return back()->with('status', 'Account settings updated successfully!');
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
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        if (!$account) {
            return back()->withErrors(['account' => 'Account not found.']);
        }

        // Cannot delete personal accounts
        if ($account->account_type === 'personal_individual') {
            return back()->withErrors(['account' => 'Personal accounts cannot be deleted.']);
        }

        // Check ownership
        $membership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$membership || $membership->account_membership_role !== 'account_owner') {
            return back()->withErrors(['account' => 'Only the account owner can delete this account.']);
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
            ->with('status', 'Account deleted successfully.');
    }

    /**
     * Upload account logo.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
        ]);

        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        if (!$account) {
            return back()->withErrors(['account' => 'Account not found.']);
        }

        // Check permission
        $membership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$membership || !in_array($membership->account_membership_role, ['account_owner', 'account_administrator'])) {
            return back()->withErrors(['account' => 'You do not have permission to edit this account.']);
        }

        $file = $request->file('logo');
        
        // Generate filename: original_accounthash_datetime.extension
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName); // Sanitize
        $extension = $file->getClientOriginalExtension();
        $accountHash = substr($account->record_unique_identifier, 0, 8);
        $datetime = now()->format('Ymd_His');
        
        $filename = "{$originalName}_{$accountHash}_{$datetime}.{$extension}";
        
        // Store in public/uploads/accounts/icons
        $path = $file->storeAs('uploads/accounts/icons', $filename, 'public');
        
        // Delete old logo if exists
        if ($account->branding_logo_image_path) {
            Storage::disk('public')->delete($account->branding_logo_image_path);
        }
        
        // Update account
        $account->update([
            'branding_logo_image_path' => $path,
        ]);

        return back()->with('status', 'Account logo updated successfully.');
    }

    /**
     * Remove account logo.
     */
    public function removeLogo()
    {
        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            return back()->withErrors(['account' => 'No active account selected.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->first();

        if (!$account) {
            return back()->withErrors(['account' => 'Account not found.']);
        }

        // Check permission
        $membership = auth()->user()->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        if (!$membership || !in_array($membership->account_membership_role, ['account_owner', 'account_administrator'])) {
            return back()->withErrors(['account' => 'You do not have permission to edit this account.']);
        }

        if ($account->branding_logo_image_path) {
            Storage::disk('public')->delete($account->branding_logo_image_path);
            $account->update(['branding_logo_image_path' => null]);
        }

        return back()->with('status', 'Account logo removed.');
    }
}
