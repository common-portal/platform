<?php

namespace App\Http\Controllers;

use App\Models\AccountApiKey;
use App\Models\AccountWebhook;
use App\Models\AccountFeeConfig;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\TenantAccountMembership;
use App\Models\TenantAccount;
use App\Models\IbanAccount;
use App\Models\CryptoWallet;
use App\Models\CryptoWalletTransaction;
use App\Services\WalletIdsService;
use App\Services\SolanaRpcService;
use App\Services\SolanaTransferService;
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
     * Customers page.
     */
    public function customers()
    {
        if (!$this->checkModulePermission('can_access_customers')) {
            abort(403, 'You do not have permission to access Customers.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        // Placeholder - in real implementation, would fetch customers data
        $customers = collect();

        return view('pages.modules.customers', [
            'customers' => $customers,
        ]);
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
     * Direct Debit transactions history page.
     */
    public function directDebitTransactions(Request $request)
    {
        if (!$this->checkModulePermission('can_view_transaction_history')) {
            abort(403, 'You do not have permission to view Transaction History.');
        }

        $activeAccountId = session('active_account_id');

        if (!$activeAccountId) {
            if (session('admin_impersonating_from') || (auth()->user() && auth()->user()->is_platform_administrator)) {
                return redirect()->route('admin.accounts')
                    ->withErrors(['account' => 'Please impersonate an account first to view transactions.']);
            }
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        $query = \App\Models\DirectDebitCollection::where('tenant_account_id', $activeAccountId)
            ->with('customer');

        // Filter: customer name
        if ($request->filled('customer_name')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('customer_full_name', 'ilike', '%' . $request->customer_name . '%');
            });
        }

        // Filter: status
        if ($request->filled('status')) {
            $statuses = (array) $request->input('status');
            $query->whereIn('status', $statuses);
        }

        // Filter: date range
        if ($request->filled('date_from')) {
            $query->where('billing_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('billing_date', '<=', $request->date_to);
        }

        // Filter: reference
        if ($request->filled('reference')) {
            $query->where('reference', 'ilike', '%' . $request->reference . '%');
        }

        // Filter: min amount
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        $collections = $query->orderBy('created_at_timestamp', 'desc')->paginate(25);

        return view('pages.modules.transactions-directdebit', [
            'collections' => $collections,
        ]);
    }

    /**
     * Refund a cleared direct debit collection.
     */
    public function directDebitRefund(Request $request, int $collection)
    {
        if (!$this->checkModulePermission('can_view_transaction_history')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $activeAccountId = session('active_account_id');
        if (!$activeAccountId) {
            return response()->json(['success' => false, 'message' => 'No active account.'], 400);
        }

        $record = \App\Models\DirectDebitCollection::where('id', $collection)
            ->where('tenant_account_id', $activeAccountId)
            ->where('status', \App\Models\DirectDebitCollection::STATUS_CLEARED)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Collection not found or not eligible for refund.'], 404);
        }

        try {
            $apiService = app(\App\Services\DirectDebitApiService::class);
            $result = $apiService->submitPayment([
                'correlationId' => $record->correlation_id . '_REFUND',
                'sourceLedgerUid' => $record->destination_ledger_uid,
                'destinationLedgerUid' => null,
                'amount' => $record->amount_minor_units,
                'reference' => 'REFUND-' . $record->reference,
                'paymentReason' => 'Direct Debit Refund',
            ]);

            if ($result['success']) {
                $record->update([
                    'status' => 'refunded',
                    'failure_reason' => 'Refund initiated by user. Refund TXN: ' . ($result['transaction_uid'] ?? 'N/A'),
                    'updated_at_timestamp' => now(),
                ]);

                \Illuminate\Support\Facades\Log::channel('directdebit')->info('DD Refund initiated', [
                    'collection_id' => $record->id,
                    'customer_id' => $record->customer_id,
                    'amount' => $record->amount,
                    'refund_txn_uid' => $result['transaction_uid'] ?? null,
                ]);

                return response()->json(['success' => true, 'message' => 'Refund initiated successfully.']);
            }

            return response()->json(['success' => false, 'message' => $result['error'] ?? 'Refund API call failed.'], 500);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('directdebit')->error('DD Refund exception', [
                'collection_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Refund failed: ' . $e->getMessage()], 500);
        }
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
     * Fees page - read-only display of account fee configs per currency.
     */
    public function fees()
    {
        if (!$this->checkModulePermission('can_view_fees')) {
            abort(403, 'You do not have permission to view Fees.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        $account = TenantAccount::find($activeAccountId);
        
        if (!$account) {
            return redirect()->route('home')->withErrors(['account' => 'Account not found.']);
        }

        $fees = AccountFeeConfig::where('account_hash', $account->record_unique_identifier)
            ->notDeleted()
            ->whereIn('currency_code', ['GBP', 'EUR'])
            ->get()
            ->keyBy('currency_code');

        return view('pages.modules.fees', [
            'account' => $account,
            'gbpFees' => $fees->get('GBP'),
            'eurFees' => $fees->get('EUR'),
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

        // Fetch balances from SH Financial API for each IBAN
        foreach ($ibans as $iban) {
            $iban->balance = $this->fetchIbanBalance($iban->iban_number);
        }

        // Group by currency
        $ibansByCurrency = $ibans->groupBy('iban_currency_iso3');

        return view('pages.modules.ibans', [
            'ibansByCurrency' => $ibansByCurrency,
            'account' => $account,
        ]);
    }

    /**
     * Fetch IBAN balance from SH Financial API
     */
    protected function fetchIbanBalance(string $ibanNumber): ?float
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get(
                'https://utilities.getmondo.co/gateway/sh-financial/get_ledger_account_balance_xramp.php',
                ['iban_account_number' => $ibanNumber]
            );

            if ($response->successful()) {
                $data = $response->json();
                // API returns balance already in decimal format as 'account_balance'
                return $data['account_balance'] ?? null;
            }

            \Illuminate\Support\Facades\Log::warning('SH Financial balance API error', [
                'iban' => $ibanNumber,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SH Financial balance API request failed', [
                'iban' => $ibanNumber,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    // ─── Crypto Wallets Module ───────────────────────────────────────────

    /**
     * Client wallets page with wallet cards and transaction history.
     */
    public function wallets()
    {
        if (!$this->checkModulePermission('can_view_wallets')) {
            abort(403, 'You do not have permission to view Wallets.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            if (session('admin_impersonating_from') || (auth()->user() && auth()->user()->is_platform_administrator)) {
                return redirect()->route('admin.accounts')
                    ->withErrors(['account' => 'Please impersonate an account first to view wallets.']);
            }
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        $account = TenantAccount::find($activeAccountId);
        
        if (!$account) {
            return redirect()->route('home')->withErrors(['account' => 'Account not found.']);
        }

        // Fetch wallets assigned to this account
        $wallets = CryptoWallet::where('account_hash', $account->record_unique_identifier)
            ->notDeleted()
            ->orderBy('wallet_currency')
            ->orderBy('wallet_friendly_name')
            ->get();

        return view('pages.modules.wallets', [
            'wallets' => $wallets,
            'account' => $account,
        ]);
    }

    /**
     * Get real-time wallet balance (JSON) — single wallet.
     */
    public function walletBalance($hash)
    {
        if (!$this->checkModulePermission('can_view_wallets')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        try {
            $activeAccountId = session('active_account_id');
            $account = TenantAccount::find($activeAccountId);

            $wallet = CryptoWallet::where('record_unique_identifier', $hash)
                ->where('account_hash', $account->record_unique_identifier)
                ->notDeleted()
                ->firstOrFail();

            $solanaRpc = app(SolanaRpcService::class);
            $balance = $this->fetchSingleWalletBalance($solanaRpc, $wallet);

            return response()->json([
                'success' => true,
                'token_balance' => $balance,
                'currency' => $wallet->wallet_currency,
                'wallet_hash' => $hash,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Wallet not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Wallet balance error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching balance'], 500);
        }
    }

    /**
     * Get balances for ALL account wallets in one call (sequential RPC with delays).
     */
    public function walletBalances()
    {
        if (!$this->checkModulePermission('can_view_wallets')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        try {
            $activeAccountId = session('active_account_id');
            $account = TenantAccount::find($activeAccountId);

            $wallets = CryptoWallet::where('account_hash', $account->record_unique_identifier)
                ->notDeleted()
                ->get();

            $solanaRpc = app(SolanaRpcService::class);
            $balances = [];

            foreach ($wallets as $i => $wallet) {
                if ($i > 0) {
                    usleep(300000); // 300ms delay between wallets
                }

                $balances[$wallet->record_unique_identifier] = [
                    'balance' => $this->fetchSingleWalletBalance($solanaRpc, $wallet),
                    'currency' => $wallet->wallet_currency,
                ];
            }

            return response()->json(['success' => true, 'balances' => $balances]);
        } catch (\Exception $e) {
            \Log::error('Wallet balances batch error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching balances'], 500);
        }
    }

    /**
     * Fetch balance for a single wallet from Solana RPC.
     */
    protected function fetchSingleWalletBalance(SolanaRpcService $solanaRpc, CryptoWallet $wallet): ?float
    {
        try {
            // SOL wallets (GAS): return native SOL balance
            if ($wallet->wallet_currency === 'SOL') {
                $solLamports = $solanaRpc->getBalance($wallet->wallet_address);
                return $solLamports !== null ? round($solLamports / 1_000_000_000, 6) : null;
            }

            // SPL token wallets: return token balance
            $mintAddress = SolanaTransferService::MINTS[$wallet->wallet_currency] ?? null;
            if ($mintAddress) {
                $tokenAccounts = $solanaRpc->getTokenAccountsByOwner($wallet->wallet_address, $mintAddress);
                if ($tokenAccounts && isset($tokenAccounts['value']) && count($tokenAccounts['value']) > 0) {
                    $tokenInfo = $tokenAccounts['value'][0]['account']['data']['parsed']['info']['tokenAmount'] ?? null;
                    if ($tokenInfo) {
                        return $tokenInfo['uiAmount'] ?? 0;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            \Log::warning('Balance fetch failed', ['wallet' => $wallet->wallet_address, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get wallet transactions (JSON).
     */
    public function walletTransactions($hash)
    {
        if (!$this->checkModulePermission('can_view_wallets')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        try {
            $activeAccountId = session('active_account_id');
            $account = TenantAccount::find($activeAccountId);

            $wallet = CryptoWallet::where('record_unique_identifier', $hash)
                ->where('account_hash', $account->record_unique_identifier)
                ->notDeleted()
                ->firstOrFail();

            $transactions = CryptoWalletTransaction::where('wallet_id', $wallet->id)
                ->orderBy('datetime_created', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'transactions' => $transactions,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Wallet not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Wallet transactions error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching transactions'], 500);
        }
    }

    /**
     * Get Solana tracking detail for a transaction (JSON).
     * Returns step-by-step tracking info from Solana RPC.
     */
    public function walletTxDetail($hash)
    {
        if (!$this->checkModulePermission('can_view_wallets')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        try {
            $activeAccountId = session('active_account_id');
            $account = TenantAccount::find($activeAccountId);

            $tx = CryptoWalletTransaction::where('record_unique_identifier', $hash)
                ->where('account_hash', $account->record_unique_identifier)
                ->firstOrFail();

            if (!$tx->solana_tx_signature) {
                return response()->json([
                    'success' => true,
                    'detail' => null,
                    'message' => 'No Solana transaction signature available yet.',
                ]);
            }

            $solanaService = app(SolanaRpcService::class);
            $detail = $solanaService->getTrackingDetail($tx->solana_tx_signature);

            return response()->json([
                'success' => true,
                'detail' => $detail,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Wallet tx detail error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching transaction detail'], 500);
        }
    }

    /**
     * Client-initiated send from their designated wallet to a 3rd-party wallet.
     */
    public function walletSend(Request $request, $hash)
    {
        if (!$this->checkModulePermission('can_view_wallets')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        try {
            $activeAccountId = session('active_account_id');
            $account = TenantAccount::find($activeAccountId);

            $wallet = CryptoWallet::where('record_unique_identifier', $hash)
                ->where('account_hash', $account->record_unique_identifier)
                ->notDeleted()
                ->active()
                ->firstOrFail();

            $validated = $request->validate([
                'to_wallet_address' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0.000001',
                'memo_note' => 'nullable|string|max:1000',
            ]);

            // Record the transaction as submitted
            $tx = CryptoWalletTransaction::create([
                'wallet_id' => $wallet->id,
                'account_hash' => $wallet->account_hash,
                'direction' => 'outgoing',
                'currency' => $wallet->wallet_currency,
                'network' => $wallet->wallet_network,
                'amount' => $validated['amount'],
                'from_wallet_address' => $wallet->wallet_address,
                'to_wallet_address' => $validated['to_wallet_address'],
                'transaction_status' => 'submitted',
                'memo_note' => $validated['memo_note'] ?? null,
                'initiated_by_member_hash' => auth()->user()->record_unique_identifier,
                'datetime_submitted' => now(),
            ]);

            // Execute the Solana SPL token transfer
            $transferService = app(SolanaTransferService::class);
            $result = $transferService->transfer(
                $wallet,
                $validated['to_wallet_address'],
                (float) $validated['amount'],
                $tx
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer completed successfully.',
                    'signature' => $result['signature'],
                    'transaction' => $tx->fresh(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Transfer failed.',
                    'transaction' => $tx->fresh(),
                ], 502);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found or inactive'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Client wallet send error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending from wallet: ' . $e->getMessage()
            ], 500);
        }
    }
}
