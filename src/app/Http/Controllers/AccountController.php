<?php

namespace App\Http\Controllers;

use App\Models\TenantAccount;
use App\Models\TenantAccountMembership;
use App\Models\Transaction;
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
}
