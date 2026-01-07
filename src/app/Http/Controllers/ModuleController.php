<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Developer Tools page - API docs and keys.
     */
    public function developer()
    {
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
        return view('pages.modules.support-create');
    }

    /**
     * Store new support ticket.
     */
    public function supportStore(Request $request)
    {
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
