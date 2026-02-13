<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\DirectDebitCollection;
use App\Services\DirectDebitApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessDirectDebitCollections extends Command
{
    protected $signature = 'directdebit:process-collections
                            {--date= : Override billing date (YYYY-MM-DD), defaults to today UTC}
                            {--dry-run : Simulate without submitting to API or recording}
                            {--tenant= : Process only a specific tenant_account_id}';

    protected $description = 'Process daily direct debit collections for all eligible active mandates';

    protected int $skipped = 0;
    protected int $failed = 0;
    protected int $submitted = 0;

    public function handle(DirectDebitApiService $apiService): int
    {
        $billingDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::now('UTC')->startOfDay();

        $isDryRun = $this->option('dry-run');

        $this->info("========================================");
        $this->info("Direct Debit Collection Processing");
        $this->info("Billing Date: {$billingDate->toDateString()}");
        $this->info($isDryRun ? "MODE: DRY RUN (no submissions)" : "MODE: LIVE");
        $this->info("========================================");

        Log::channel('directdebit')->info('DD Cron started', [
            'billing_date' => $billingDate->toDateString(),
            'dry_run' => $isDryRun,
        ]);

        // STEP 1: Query eligible mandates
        $this->info("\n[STEP 1] Querying eligible mandates...");
        $eligibleCustomers = $this->getEligibleMandates();

        if ($eligibleCustomers->isEmpty()) {
            $this->warn("No eligible mandates found. Exiting.");
            Log::channel('directdebit')->info('DD Cron: No eligible mandates');
            return Command::SUCCESS;
        }

        $this->info("Found {$eligibleCustomers->count()} eligible mandate(s).");

        // STEP 2: Filter by today's billing schedule
        $this->info("\n[STEP 2] Filtering by billing schedule for {$billingDate->toDateString()}...");
        $dueCustomers = $this->filterByBillingSchedule($eligibleCustomers, $billingDate);

        if ($dueCustomers->isEmpty()) {
            $this->info("No mandates due for billing today. Exiting.");
            Log::channel('directdebit')->info('DD Cron: No mandates due today');
            return Command::SUCCESS;
        }

        $this->info("{$dueCustomers->count()} mandate(s) due for billing today.");

        // STEP 3: Check for duplicates / already processed
        $this->info("\n[STEP 3] Checking for already-processed collections...");
        $newCustomers = $this->filterAlreadyProcessed($dueCustomers, $billingDate);

        if ($newCustomers->isEmpty()) {
            $this->info("All due mandates already processed today. Exiting.");
            Log::channel('directdebit')->info('DD Cron: All already processed');
            return Command::SUCCESS;
        }

        $this->info("{$newCustomers->count()} new collection(s) to process.");

        // STEP 4: Build payment instructions
        $this->info("\n[STEP 4] Building payment instructions...");
        $instructions = $this->buildPaymentInstructions($newCustomers, $billingDate);

        if ($instructions->isEmpty()) {
            $this->warn("No valid payment instructions built. Exiting.");
            return Command::SUCCESS;
        }

        $this->info("{$instructions->count()} payment instruction(s) built.");

        // STEP 5 & 6: Submit to API and record locally
        if ($isDryRun) {
            $this->info("\n[DRY RUN] Would submit {$instructions->count()} collection(s):");
            foreach ($instructions as $instruction) {
                $this->line("  - {$instruction['customer_name']}: {$instruction['amount']} {$instruction['currency']} → {$instruction['reference']}");
            }
        } else {
            $this->info("\n[STEP 5] Submitting to SH Financial API...");
            $this->submitAndRecord($instructions, $billingDate, $apiService);
        }

        // STEP 7: Summary
        $this->info("\n========================================");
        $this->info("Processing Complete");
        $this->info("  Submitted: {$this->submitted}");
        $this->info("  Skipped:   {$this->skipped}");
        $this->info("  Failed:    {$this->failed}");
        $this->info("========================================");

        Log::channel('directdebit')->info('DD Cron completed', [
            'billing_date' => $billingDate->toDateString(),
            'submitted' => $this->submitted,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
        ]);

        return $this->failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * STEP 1: Query all eligible mandates (confirmed + active + has settlement IBAN + has amount).
     */
    protected function getEligibleMandates()
    {
        $query = Customer::where('mandate_status', 'mandate_confirmed')
            ->where('mandate_active_or_paused', 'active')
            ->whereNotNull('settlement_iban_hash')
            ->where('billing_amount', '>', 0)
            ->with('settlementIban', 'tenantAccount');

        if ($this->option('tenant')) {
            $query->where('tenant_account_id', $this->option('tenant'));
        }

        return $query->get();
    }

    /**
     * STEP 2: Filter mandates by whether today is a billing day per their schedule.
     */
    protected function filterByBillingSchedule($customers, Carbon $billingDate)
    {
        return $customers->filter(function (Customer $customer) use ($billingDate) {
            // Check start date
            if ($customer->billing_start_date && $billingDate->lt($customer->billing_start_date)) {
                $this->skipped++;
                $this->line("  Skip {$customer->customer_full_name}: start date {$customer->billing_start_date->toDateString()} not reached");
                return false;
            }

            $frequency = $customer->recurring_frequency;
            $billingDates = $customer->billing_dates ?? [];

            return match ($frequency) {
                'daily' => true,
                'weekly' => $this->isWeeklyBillingDay($billingDate, $billingDates, $customer),
                'monthly' => $this->isMonthlyBillingDay($billingDate, $billingDates, $customer),
                default => false,
            };
        });
    }

    /**
     * Check if today's day name matches any in the billing_dates array.
     * e.g. ["monday", "wednesday", "friday"]
     */
    protected function isWeeklyBillingDay(Carbon $billingDate, array $billingDates, Customer $customer): bool
    {
        $todayName = strtolower($billingDate->englishDayOfWeek);
        $normalizedDates = array_map('strtolower', $billingDates);
        $match = in_array($todayName, $normalizedDates);

        if (!$match) {
            $this->skipped++;
            $this->line("  Skip {$customer->customer_full_name}: weekly, today is {$todayName}, billing days: " . implode(',', $billingDates));
        }

        return $match;
    }

    /**
     * Check if today's day-of-month matches any in the billing_dates array.
     * e.g. [1, 15]
     */
    protected function isMonthlyBillingDay(Carbon $billingDate, array $billingDates, Customer $customer): bool
    {
        $todayDay = $billingDate->day;
        $normalizedDates = array_map('intval', $billingDates);
        $match = in_array($todayDay, $normalizedDates);

        if (!$match) {
            $this->skipped++;
            $this->line("  Skip {$customer->customer_full_name}: monthly, today is day {$todayDay}, billing days: " . implode(',', $billingDates));
        }

        return $match;
    }

    /**
     * STEP 3: Filter out mandates already processed today (idempotency).
     */
    protected function filterAlreadyProcessed($customers, Carbon $billingDate)
    {
        return $customers->filter(function (Customer $customer) use ($billingDate) {
            $exists = DirectDebitCollection::where('customer_id', $customer->id)
                ->where('billing_date', $billingDate->toDateString())
                ->whereIn('status', [
                    DirectDebitCollection::STATUS_PENDING,
                    DirectDebitCollection::STATUS_SUBMITTED,
                    DirectDebitCollection::STATUS_CLEARED,
                ])
                ->exists();

            if ($exists) {
                $this->skipped++;
                $this->line("  Skip {$customer->customer_full_name}: already processed for {$billingDate->toDateString()}");
                return false;
            }

            return true;
        });
    }

    /**
     * STEP 4: Build payment instruction data for each eligible mandate.
     */
    protected function buildPaymentInstructions($customers, Carbon $billingDate)
    {
        return $customers->map(function (Customer $customer) use ($billingDate) {
            $settlementIban = $customer->settlementIban;

            if (!$settlementIban || !$settlementIban->iban_ledger) {
                $this->failed++;
                $this->error("  FAIL {$customer->customer_full_name}: No settlement IBAN ledger UUID found");
                Log::channel('directdebit')->error('DD: Missing settlement ledger', [
                    'customer_id' => $customer->id,
                    'settlement_iban_hash' => $customer->settlement_iban_hash,
                ]);
                return null;
            }

            $amountMinor = (int) round($customer->billing_amount * 100);
            $reference = $this->buildReference($customer, $billingDate);

            // Determine sequence type (first vs recurring)
            $hasHistory = DirectDebitCollection::where('customer_id', $customer->id)
                ->where('status', DirectDebitCollection::STATUS_CLEARED)
                ->exists();

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_full_name,
                'tenant_account_id' => $customer->tenant_account_id,
                'source_iban' => $customer->customer_iban,
                'destination_iban' => $settlementIban->iban_number,
                'destination_ledger_uid' => $settlementIban->iban_ledger,
                'amount' => $customer->billing_amount,
                'amount_minor_units' => $amountMinor,
                'currency' => $customer->billing_currency ?? 'EUR',
                'correlation_id' => (string) Str::uuid(), // UUID for SH Financial + webhook matching
                'reference' => $reference,
                'sequence_type' => $hasHistory ? DirectDebitCollection::SEQ_RECURRING : DirectDebitCollection::SEQ_FIRST,
                // SDD-specific fields for /api/v1/payment/sdd/create
                'mandate_id' => $customer->record_unique_identifier,
                'mandate_date_of_signature' => $customer->mandate_confirmed_at
                    ? $customer->mandate_confirmed_at->toDateString()
                    : now()->toDateString(),
                'settlement_date' => $billingDate->toDateString(),
            ];
        })->filter(); // Remove nulls
    }

    /**
     * STEP 5 & 6: Submit each collection to the API and record locally.
     */
    protected function submitAndRecord($instructions, Carbon $billingDate, DirectDebitApiService $apiService): void
    {
        // Group by tenant account for potential batch processing
        $grouped = $instructions->groupBy('tenant_account_id');

        foreach ($grouped as $tenantAccountId => $tenantInstructions) {
            $this->info("  Processing tenant #{$tenantAccountId} ({$tenantInstructions->count()} collections)...");

            foreach ($tenantInstructions as $instruction) {
                $collection = null;
                try {
                    // Look up the customer's (debtor) ledgerUid from their IBAN
                    $sourceLedgerUid = null;
                    if (!empty($instruction['source_iban'])) {
                        $sourceLedgerUid = $apiService->lookupLedgerUidByIban($instruction['source_iban']);
                    }

                    if (!$sourceLedgerUid) {
                        $this->failed++;
                        $this->error("    ✗ {$instruction['customer_name']}: Could not resolve source IBAN {$instruction['source_iban']} to ledgerUid");
                        Log::channel('directdebit')->error('DD: Source IBAN ledger lookup failed', [
                            'customer_id' => $instruction['customer_id'],
                            'source_iban' => $instruction['source_iban'],
                        ]);
                        continue;
                    }

                    // Record locally first (pending)
                    $collection = DirectDebitCollection::create([
                        'tenant_account_id' => $instruction['tenant_account_id'],
                        'customer_id' => $instruction['customer_id'],
                        'correlation_id' => $instruction['correlation_id'],
                        'reference' => $instruction['reference'],
                        'amount' => $instruction['amount'],
                        'currency' => $instruction['currency'],
                        'amount_minor_units' => $instruction['amount_minor_units'],
                        'source_iban' => $instruction['source_iban'],
                        'destination_iban' => $instruction['destination_iban'],
                        'destination_ledger_uid' => $instruction['destination_ledger_uid'],
                        'billing_date' => $billingDate->toDateString(),
                        'sequence_type' => $instruction['sequence_type'],
                        'status' => DirectDebitCollection::STATUS_PENDING,
                        'created_at_timestamp' => now(),
                        'updated_at_timestamp' => now(),
                    ]);

                    // Submit to SH Financial SDD API
                    $result = $apiService->submitPayment([
                        'correlationId' => $instruction['correlation_id'], // UUID stored in DB + sent to SH Financial
                        'destinationLedgerUid' => $instruction['destination_ledger_uid'],
                        'sourceLedgerUid' => $sourceLedgerUid,
                        'amount' => $instruction['amount_minor_units'],
                        'reference' => $instruction['reference'],
                        'sequenceType' => $instruction['sequence_type'],
                        'mandateId' => $instruction['mandate_id'],
                        'mandateDateOfSignature' => $instruction['mandate_date_of_signature'],
                        'settlementDate' => $instruction['settlement_date'],
                    ]);

                    if ($result['success']) {
                        $collection->markSubmitted(
                            $result['transaction_uid'] ?? null,
                            $result['unique_key'] ?? null
                        );
                        $this->submitted++;
                        $this->info("    ✓ {$instruction['customer_name']}: {$instruction['amount']} {$instruction['currency']} submitted (txn: {$result['transaction_uid']})");
                    } else {
                        $collection->markFailed($result['error'] ?? 'Unknown API error');
                        $this->failed++;
                        $this->error("    ✗ {$instruction['customer_name']}: {$result['error']}");
                    }
                } catch (\Exception $e) {
                    $this->failed++;
                    $this->error("    ✗ {$instruction['customer_name']}: Exception - {$e->getMessage()}");
                    Log::channel('directdebit')->error('DD collection exception', [
                        'customer_id' => $instruction['customer_id'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    if (isset($collection)) {
                        $collection->markFailed($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Build human-readable payment reference: DD-{NAME}-{YYYYMMDD}-{SEQ}
     */
    protected function buildReference(Customer $customer, Carbon $billingDate): string
    {
        $nameSlug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $customer->customer_full_name), 0, 12));
        return "DD-{$nameSlug}-{$billingDate->format('Ymd')}";
    }
}
