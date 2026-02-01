<?php

namespace App\Http\Controllers;

use App\Models\AccountApiKey;
use App\Models\AccountWebhook;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\TenantAccountMembership;
use App\Models\TenantAccount;
use App\Models\IbanAccount;
use App\Mail\SupportTicketConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ModuleController extends Controller
{
    /**
     * Check if user has permission for a module.
     */
    protected function checkModulePermission(string $permission): bool
    {
        $user = auth()->user();
        $activeAccountId = session('active_account_id');

        // Platform admins bypass all checks
        if ($user->is_platform_administrator) {
            return true;
        }

        if (!$activeAccountId) {
            return false;
        }

        $membership = $user->account_memberships()
            ->where('tenant_account_id', $activeAccountId)
            ->first();

        return $membership && $membership->hasPermission($permission);
    }

    /**
     * Developer Tools page - API docs and keys.
     */
    public function developer()
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403, 'You do not have permission to access Developer Tools.');
        }

        $activeAccountId = session('active_account_id');
        $isAdminImpersonating = session('admin_impersonating_from') !== null;
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        // If admin is impersonating, fetch account directly
        if ($isAdminImpersonating && auth()->user()->is_platform_administrator) {
            $account = \App\Models\TenantAccount::where('id', $activeAccountId)
                ->where('is_soft_deleted', false)
                ->first();
        } else {
            $account = auth()->user()->tenant_accounts()
                ->where('tenant_accounts.id', $activeAccountId)
                ->where('is_soft_deleted', false)
                ->first();
        }

        if (!$account) {
            return redirect()->route('home')->withErrors(['account' => 'Account not found.']);
        }

        $webhooks = AccountWebhook::where('tenant_account_id', $activeAccountId)
            ->orderBy('created_at_timestamp', 'desc')
            ->get();

        $apiKeys = AccountApiKey::where('tenant_account_id', $activeAccountId)
            ->orderBy('created_at_timestamp', 'desc')
            ->get();

        return view('pages.modules.developer', [
            'account' => $account,
            'webhooks' => $webhooks,
            'apiKeys' => $apiKeys,
            'activeTab' => request()->route('tab', 'documentation'),
        ]);
    }

    /**
     * Store a new webhook.
     */
    public function webhookStore(Request $request)
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403);
        }

        $request->validate([
            'webhook_name' => 'required|string|max:100',
            'webhook_url' => 'required|url|max:500',
        ]);

        $activeAccountId = session('active_account_id');
        if (!$activeAccountId) {
            return back()->withErrors(['webhook' => 'No active account selected.']);
        }

        AccountWebhook::create([
            'tenant_account_id' => $activeAccountId,
            'webhook_name' => $request->webhook_name,
            'webhook_url' => $request->webhook_url,
            'is_enabled' => true,
        ]);

        return redirect()->route('modules.developer.tab', ['tab' => 'webhooks'])
            ->with('status', 'Webhook created successfully.');
    }

    /**
     * Update an existing webhook.
     */
    public function webhookUpdate(Request $request, $webhook_id)
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403);
        }

        $request->validate([
            'webhook_name' => 'required|string|max:100',
            'webhook_url' => 'required|url|max:500',
        ]);

        $activeAccountId = session('active_account_id');
        $webhook = AccountWebhook::where('id', $webhook_id)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        $webhook->update([
            'webhook_name' => $request->webhook_name,
            'webhook_url' => $request->webhook_url,
        ]);

        return redirect()->route('modules.developer.tab', ['tab' => 'webhooks'])
            ->with('status', 'Webhook updated successfully.');
    }

    /**
     * Toggle webhook enabled/disabled status.
     */
    public function webhookToggle($webhook_id)
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403);
        }

        $activeAccountId = session('active_account_id');
        $webhook = AccountWebhook::where('id', $webhook_id)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        $webhook->update(['is_enabled' => !$webhook->is_enabled]);

        $status = $webhook->is_enabled ? 'enabled' : 'disabled';
        return redirect()->route('modules.developer.tab', ['tab' => 'webhooks'])
            ->with('status', "Webhook {$status} successfully.");
    }

    /**
     * Delete a webhook.
     */
    public function webhookDestroy($webhook_id)
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403);
        }

        $activeAccountId = session('active_account_id');
        $webhook = AccountWebhook::where('id', $webhook_id)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        $webhook->delete();

        return redirect()->route('modules.developer.tab', ['tab' => 'webhooks'])
            ->with('status', 'Webhook deleted successfully.');
    }

    /**
     * Store a new API key.
     */
    public function apiKeyStore(Request $request)
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403);
        }

        $request->validate([
            'api_key_name' => 'required|string|max:255',
        ]);

        $activeAccountId = session('active_account_id');

        AccountApiKey::create([
            'tenant_account_id' => $activeAccountId,
            'api_key_name' => $request->api_key_name,
        ]);

        return redirect()->route('modules.developer.tab', ['tab' => 'keys'])
            ->with('status', 'API key generated successfully.');
    }

    /**
     * Toggle API key enabled/disabled.
     */
    public function apiKeyToggle($api_key_id)
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403);
        }

        $activeAccountId = session('active_account_id');
        $apiKey = AccountApiKey::where('id', $api_key_id)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        $apiKey->is_enabled = !$apiKey->is_enabled;
        $apiKey->save();

        $status = $apiKey->is_enabled ? 'enabled' : 'disabled';
        return redirect()->route('modules.developer.tab', ['tab' => 'keys'])
            ->with('status', "API key {$status} successfully.");
    }

    /**
     * Delete an API key.
     */
    public function apiKeyDestroy($api_key_id)
    {
        if (!$this->checkModulePermission('can_access_developer_tools')) {
            abort(403);
        }

        $activeAccountId = session('active_account_id');
        $apiKey = AccountApiKey::where('id', $api_key_id)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        $apiKey->delete();

        return redirect()->route('modules.developer.tab', ['tab' => 'keys'])
            ->with('status', 'API key deleted successfully.');
    }

    /**
     * Support Tickets list.
     */
    public function supportIndex()
    {
        if (!$this->checkModulePermission('can_access_support_tickets')) {
            abort(403, 'You do not have permission to access Support Tickets.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        $tickets = SupportTicket::where('tenant_account_id', $activeAccountId)
            ->with(['attachments', 'created_by_member', 'assigned_to_administrator', 'messages.author_member', 'messages.author_admin'])
            ->withCount(['attachments', 'messages'])
            ->orderBy('created_at_timestamp', 'desc')
            ->paginate(15);

        return view('pages.modules.support-index', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Create new support ticket form.
     */
    public function supportCreate()
    {
        if (!$this->checkModulePermission('can_access_support_tickets')) {
            abort(403, 'You do not have permission to access Support Tickets.');
        }

        return view('pages.modules.support-create');
    }

    /**
     * Store new support ticket.
     */
    public function supportStore(Request $request)
    {
        if (!$this->checkModulePermission('can_access_support_tickets')) {
            abort(403, 'You do not have permission to access Support Tickets.');
        }

        $request->validate([
            'ticket_category' => 'required|string|max:100',
            'ticket_subject_line' => 'required|string|max:500',
            'ticket_description_body' => 'required|string|max:10000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip',
        ]);

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        // Get account and member hashes (full UUIDs for filename and DB)
        $account = TenantAccount::find($activeAccountId);
        $accountHash = $account->record_unique_identifier ?? md5($activeAccountId);
        $memberHash = auth()->user()->record_unique_identifier ?? md5(auth()->id());

        $ticket = SupportTicket::create([
            'tenant_account_id' => $activeAccountId,
            'created_by_member_id' => auth()->id(),
            'ticket_category' => $request->ticket_category,
            'ticket_subject_line' => $request->ticket_subject_line,
            'ticket_description_body' => $request->ticket_description_body,
            'ticket_status' => 'ticket_open',
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                SupportTicketAttachment::storeAuthenticatedFile($file, $ticket->id, $accountHash, $memberHash);
            }
        }

        // Send confirmation email to member
        $member = auth()->user();
        if ($member->login_email_address) {
            Mail::to($member->login_email_address)->send(
                new SupportTicketConfirmation($ticket, $member->full_name ?? 'Member')
            );
        }

        return redirect()->route('modules.support.index')
            ->with('status', 'Support ticket created successfully. A confirmation email has been sent.');
    }

    /**
     * View single support ticket.
     */
    public function supportShow($ticket_id)
    {
        if (!$this->checkModulePermission('can_access_support_tickets')) {
            abort(403, 'You do not have permission to access Support Tickets.');
        }

        $activeAccountId = session('active_account_id');
        
        $ticket = SupportTicket::where('id', $ticket_id)
            ->where('tenant_account_id', $activeAccountId)
            ->with(['created_by_member', 'assigned_to_administrator'])
            ->firstOrFail();

        return view('pages.modules.support-show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Add a reply message to a support ticket.
     */
    public function supportReply(Request $request, $ticket_id)
    {
        if (!$this->checkModulePermission('can_access_support_tickets')) {
            abort(403, 'You do not have permission to access Support Tickets.');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $activeAccountId = session('active_account_id');
        
        $ticket = SupportTicket::where('id', $ticket_id)
            ->where('tenant_account_id', $activeAccountId)
            ->firstOrFail();

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'author_member_id' => auth()->id(),
            'message_type' => 'member_reply',
            'message_body' => $request->message,
        ]);

        // Reopen ticket if it was resolved/closed
        if (in_array($ticket->ticket_status, ['ticket_resolved', 'ticket_closed'])) {
            $ticket->update(['ticket_status' => 'ticket_open']);
        }

        return redirect()->route('modules.support.index')
            ->with('status', 'Reply added successfully.');
    }

    /**
     * Transactions history page.
     */
    public function transactions(Request $request)
    {
        // Debug logging - FIRST THING
        \Log::info('=== TRANSACTIONS METHOD HIT ===', [
            'timestamp' => now(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'is_admin' => auth()->user() ? auth()->user()->is_platform_administrator : null,
            'active_account_id' => session('active_account_id'),
            'admin_impersonating_from' => session('admin_impersonating_from'),
            'transaction_id_param' => $request->input('transaction_id'),
        ]);

        if (!$this->checkModulePermission('can_view_transaction_history')) {
            \Log::warning('Permission denied for transaction history');
            abort(403, 'You do not have permission to view Transaction History.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            \Log::warning('No active_account_id in session');
            // If admin is impersonating, redirect back to admin panel instead of home
            if (session('admin_impersonating_from') || (auth()->user() && auth()->user()->is_platform_administrator)) {
                return redirect()->route('admin.accounts')
                    ->withErrors(['account' => 'Please impersonate an account first to view transactions.']);
            }
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        // Build query with filters
        $query = \App\Models\Transaction::where('tenant_account_id', $activeAccountId);

        // Transaction Hash search (search by record_unique_identifier only)
        if ($request->filled('transaction_id')) {
            $transactionHash = $request->transaction_id;
            \Log::info('Transaction Hash search', [
                'transaction_hash' => $transactionHash,
            ]);
            
            // Only search by record_unique_identifier - never by auto-increment id
            $query->where('record_unique_identifier', 'like', '%' . $transactionHash . '%');
        }

        // Received amount filter
        if ($request->filled('received_amount')) {
            $query->where('amount', '>=', $request->received_amount);
        }

        // Received currency filter
        if ($request->filled('received_currency')) {
            $query->where('currency_code', $request->received_currency);
        }

        // Date received range
        if ($request->filled('date_received_from')) {
            $query->where('datetime_received', '>=', $request->date_received_from . ' 00:00:00');
        }
        if ($request->filled('date_received_through')) {
            $query->where('datetime_received', '<=', $request->date_received_through . ' 23:59:59.999999');
        }

        // Transaction status filter (default to all if none selected)
        $statuses = $request->input('status', ['received', 'exchanged', 'settled']);
        if (!empty($statuses)) {
            $query->whereIn('transaction_status', $statuses);
        }

        // Exchange amount filter
        if ($request->filled('exchange_amount')) {
            $query->where('settlement_amount', '>=', $request->exchange_amount);
        }

        // Date exchanged range
        if ($request->filled('date_exchanged_from')) {
            $query->where('datetime_exchanged', '>=', $request->date_exchanged_from . ' 00:00:00');
        }
        if ($request->filled('date_exchanged_through')) {
            $query->where('datetime_exchanged', '<=', $request->date_exchanged_through . ' 23:59:59.999999');
        }

        // Settlement amount filter
        if ($request->filled('settlement_amount')) {
            $query->where('final_settlement_amount', '>=', $request->settlement_amount);
        }

        // Date settled range
        if ($request->filled('date_settled_from')) {
            $query->where('datetime_settled', '>=', $request->date_settled_from . ' 00:00:00');
        }
        if ($request->filled('date_settled_through')) {
            $query->where('datetime_settled', '<=', $request->date_settled_through . ' 23:59:59.999999');
        }

        // Date updated range
        if ($request->filled('date_updated_from')) {
            $query->where('datetime_updated', '>=', $request->date_updated_from . ' 00:00:00');
        }
        if ($request->filled('date_updated_through')) {
            $query->where('datetime_updated', '<=', $request->date_updated_through . ' 23:59:59.999999');
        }

        // Fetch filtered transactions
        try {
            \Log::info('Executing transaction query', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
            $transactions = $query->orderBy('datetime_created', 'desc')->get();
        } catch (\Exception $e) {
            \Log::error('Transaction query failed', [
                'error' => $e->getMessage(),
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);
            return redirect()->back()
                ->withErrors(['query' => 'There was an error searching transactions. Please try different search criteria.'])
                ->withInput();
        }

        return view('pages.modules.transactions', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Billing history page.
     */
    public function billing()
    {
        if (!$this->checkModulePermission('can_view_billing_history')) {
            abort(403, 'You do not have permission to view Billing History.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        // Placeholder - in real implementation, would fetch from billing/invoices table
        $invoices = collect();

        return view('pages.modules.billing', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * IBANs page - display IBANs grouped by currency.
     */
    public function ibans()
    {
        if (!$this->checkModulePermission('can_view_ibans')) {
            abort(403, 'You do not have permission to view IBANs.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        $account = TenantAccount::find($activeAccountId);
        
        if (!$account) {
            return redirect()->route('home')->withErrors(['account' => 'Account not found.']);
        }

        // Fetch IBANs for this account, grouped by currency
        $ibans = IbanAccount::where('account_hash', $account->record_unique_identifier)
            ->notDeleted()
            ->with('host_bank')
            ->orderBy('iban_currency_iso3')
            ->orderBy('iban_friendly_name')
            ->get();

        // Group by currency
        $ibansByCurrency = $ibans->groupBy('iban_currency_iso3');

        return view('pages.modules.ibans', [
            'ibansByCurrency' => $ibansByCurrency,
            'account' => $account,
        ]);
    }
}
