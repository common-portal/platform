<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\TenantAccountMembership;
use Illuminate\Http\Request;

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
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        $account = auth()->user()->tenant_accounts()
            ->where('tenant_accounts.id', $activeAccountId)
            ->where('is_soft_deleted', false)
            ->first();

        if (!$account) {
            return redirect()->route('home')->withErrors(['account' => 'Account not found.']);
        }

        return view('pages.modules.developer', [
            'account' => $account,
        ]);
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
            'ticket_subject_line' => 'required|string|max:500',
            'ticket_description_body' => 'required|string|max:10000',
        ]);

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        SupportTicket::create([
            'tenant_account_id' => $activeAccountId,
            'created_by_member_id' => auth()->id(),
            'ticket_subject_line' => $request->ticket_subject_line,
            'ticket_description_body' => $request->ticket_description_body,
            'ticket_status' => 'ticket_open',
        ]);

        return redirect()->route('modules.support.index')
            ->with('status', 'Support ticket created successfully.');
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
     * Transactions history page.
     */
    public function transactions()
    {
        if (!$this->checkModulePermission('can_view_transaction_history')) {
            abort(403, 'You do not have permission to view Transaction History.');
        }

        $activeAccountId = session('active_account_id');
        
        if (!$activeAccountId) {
            return redirect()->route('home')->withErrors(['account' => 'Please select an account first.']);
        }

        // Placeholder - in real implementation, would fetch from transactions table
        $transactions = collect();

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
}
