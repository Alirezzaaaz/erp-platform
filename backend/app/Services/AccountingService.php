<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    /**
     * ثبت سند حسابداری دوبل
     *
     * @param  array  $lines  آرایه‌ای از ['account_code' => '1101', 'debit' => 100, 'credit' => 0, 'description' => '...']
     */
    public function createJournalEntry(
        int $tenantId,
        array $lines,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $entryDate = null,
        ?int $userId = null,
        bool $autoApprove = false
    ): JournalEntry {
        if (empty($lines)) {
            throw new InvalidArgumentException('سند باید حداقل یک ردیف داشته باشد.');
        }

        return DB::transaction(function () use (
            $tenantId, $lines, $description, $referenceType, $referenceId,
            $entryDate, $userId, $autoApprove
        ) {
            // بررسی تراز
            $totalDebit = collect($lines)->sum('debit');
            $totalCredit = collect($lines)->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new InvalidArgumentException(
                    "سند تراز نیست. جمع بدهکار: {$totalDebit}، جمع بستانکار: {$totalCredit}"
                );
            }

            // دریافت سال مالی فعال
            $fiscalYear = FiscalYear::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (!$fiscalYear) {
                throw new InvalidArgumentException('هیچ سال مالی فعالی وجود ندارد.');
            }

            // شماره سند
            $number = $this->generateEntryNumber($tenantId, $fiscalYear->id);

            // ایجاد سند
            $entry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'fiscal_year_id' => $fiscalYear->id,
                'number' => $number,
                'entry_date' => $entryDate ?? now()->toDateString(),
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => $autoApprove ? 'approved' : 'draft',
                'created_by' => $userId ?? auth('api')->id(),
                'approved_by' => $autoApprove ? ($userId ?? auth('api')->id()) : null,
                'approved_at' => $autoApprove ? now() : null,
            ]);

            // ایجاد ردیف‌ها
            foreach ($lines as $index => $line) {
                $account = $this->resolveAccount($tenantId, $line);

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                    'row_order' => $index,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    /**
     * ثبت خودکار سند فروش
     */
    public function recordSaleEntry(
        int $tenantId,
        float $totalAmount,
        float $taxAmount,
        float $costOfGoods,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): JournalEntry {
        $netSale = $totalAmount - $taxAmount;

        $lines = [
            // بدهکار: حساب‌های دریافتنی
            ['account_code' => '110201', 'debit' => $totalAmount, 'credit' => 0, 'description' => 'طلب از مشتری'],
            // بستانکار: درآمد فروش
            ['account_code' => '4101', 'debit' => 0, 'credit' => $netSale, 'description' => 'درآمد فروش'],
            // بستانکار: مالیات ارزش افزوده
            ['account_code' => '210401', 'debit' => 0, 'credit' => $taxAmount, 'description' => 'مالیات فروش'],
            // بدهکار: بهای تمام‌شده
            ['account_code' => '5101', 'debit' => $costOfGoods, 'credit' => 0, 'description' => 'بهای تمام‌شده کالای فروش رفته'],
            // بستانکار: موجودی کالا
            ['account_code' => '110301', 'debit' => 0, 'credit' => $costOfGoods, 'description' => 'کاهش موجودی'],
        ];

        return $this->createJournalEntry(
            tenantId: $tenantId,
            lines: $lines,
            description: 'ثبت خودکار فروش',
            referenceType: $referenceType,
            referenceId: $referenceId,
            userId: $userId,
            autoApprove: true
        );
    }

    /**
     * ثبت خودکار سند خرید
     */
    public function recordPurchaseEntry(
        int $tenantId,
        float $totalAmount,
        float $taxAmount,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): JournalEntry {
        $netPurchase = $totalAmount - $taxAmount;

        $lines = [
            // بدهکار: موجودی کالا
            ['account_code' => '110301', 'debit' => $netPurchase, 'credit' => 0, 'description' => 'افزایش موجودی'],
            // بدهکار: مالیات ارزش افزوده (خرید)
            ['account_code' => '1105', 'debit' => $taxAmount, 'credit' => 0, 'description' => 'مالیات خرید'],
            // بستانکار: حساب‌های پرداختنی
            ['account_code' => '210101', 'debit' => 0, 'credit' => $totalAmount, 'description' => 'بدهی به تأمین‌کننده'],
        ];

        return $this->createJournalEntry(
            tenantId: $tenantId,
            lines: $lines,
            description: 'ثبت خودکار خرید',
            referenceType: $referenceType,
            referenceId: $referenceId,
            userId: $userId,
            autoApprove: true
        );
    }

    /**
     * تولید شماره سند یکتا
     */
    protected function generateEntryNumber(int $tenantId, int $fiscalYearId): string
    {
        $lastEntry = JournalEntry::where('tenant_id', $tenantId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastEntry
            ? ((int) str_replace('JE-', '', $lastEntry->number)) + 1
            : 1;

        return 'JE-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * یافتن حساب بر اساس کد یا ID
     */
    protected function resolveAccount(int $tenantId, array $line): Account
    {
        if (isset($line['account_id'])) {
            $account = Account::where('tenant_id', $tenantId)->find($line['account_id']);
            if (!$account) {
                throw new InvalidArgumentException("حساب با ID {$line['account_id']} یافت نشد.");
            }
            return $account;
        }

        if (isset($line['account_code'])) {
            $account = Account::where('tenant_id', $tenantId)
                ->where('code', $line['account_code'])
                ->first();
            if (!$account) {
                throw new InvalidArgumentException("حساب با کد {$line['account_code']} یافت نشد.");
            }
            return $account;
        }

        throw new InvalidArgumentException('هر ردیف سند باید account_id یا account_code داشته باشد.');
    }

    /**
     * گزارش دفتر کل یک حساب در بازه زمانی
     */
    public function getAccountLedger(
        int $tenantId,
        int $accountId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $account = Account::where('tenant_id', $tenantId)->findOrFail($accountId);

        $query = JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($tenantId, $fromDate, $toDate) {
                $q->where('tenant_id', $tenantId)
                  ->whereIn('status', ['approved'])
                  ->when($fromDate, fn ($q) => $q->where('entry_date', '>=', $fromDate))
                  ->when($toDate, fn ($q) => $q->where('entry_date', '<=', $toDate));
            })
            ->where('account_id', $accountId)
            ->with('journalEntry:id,number,entry_date,description');

        // موجودی اول دوره
        $openingBalance = 0;
        if ($fromDate) {
            $openingQuery = JournalEntryLine::query()
                ->whereHas('journalEntry', function ($q) use ($tenantId, $fromDate) {
                    $q->where('tenant_id', $tenantId)
                      ->where('status', 'approved')
                      ->where('entry_date', '<', $fromDate);
                })
                ->where('account_id', $accountId);

            $openingDebit = (float) $openingQuery->clone()->sum('debit');
            $openingCredit = (float) $openingQuery->clone()->sum('credit');
            $openingBalance = $account->nature === 'debit'
                ? $openingDebit - $openingCredit
                : $openingCredit - $openingDebit;
        }

        $lines = $query->orderBy('journal_entry_id')->get();

        $runningBalance = $openingBalance;
        $items = $lines->map(function ($line) use (&$runningBalance, $account) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $runningBalance += $account->nature === 'debit'
                ? ($debit - $credit)
                : ($credit - $debit);

            return [
                'entry_id' => $line->journalEntry->id,
                'entry_number' => $line->journalEntry->number,
                'entry_date' => $line->journalEntry->entry_date?->toDateString(),
                'description' => $line->description ?? $line->journalEntry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        });

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'nature' => $account->nature,
            ],
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'total_debit' => (float) $lines->sum('debit'),
            'total_credit' => (float) $lines->sum('credit'),
            'items' => $items,
        ];
    }

    /**
     * تراز آزمایشی
     */
    public function getTrialBalance(
        int $tenantId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $accounts = Account::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $result = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $query = JournalEntryLine::query()
                ->whereHas('journalEntry', function ($q) use ($tenantId, $fromDate, $toDate) {
                    $q->where('tenant_id', $tenantId)
                      ->where('status', 'approved')
                      ->when($fromDate, fn ($q) => $q->where('entry_date', '>=', $fromDate))
                      ->when($toDate, fn ($q) => $q->where('entry_date', '<=', $toDate));
                })
                ->where('account_id', $account->id);

            $debit = (float) $query->clone()->sum('debit');
            $credit = (float) $query->clone()->sum('credit');

            if ($debit === 0.0 && $credit === 0.0) {
                continue;
            }

            $balance = $account->nature === 'debit' ? $debit - $credit : $credit - $debit;

            $result[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'nature' => $account->nature,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'accounts' => $result,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /**
     * ترازنامه
     */
    public function getBalanceSheet(int $tenantId, ?string $toDate = null): array
    {
        $trial = $this->getTrialBalance($tenantId, null, $toDate);

        $assets = [];
        $liabilities = [];
        $equity = [];

        foreach ($trial['accounts'] as $row) {
            $type = $row['type'];
            $amount = match ($type) {
                'asset' => $row['debit'] - $row['credit'],
                'liability' => $row['credit'] - $row['debit'],
                'equity' => $row['credit'] - $row['debit'],
                default => 0,
            };

            if ($type === 'asset' && $amount != 0) {
                $assets[] = array_merge($row, ['amount' => $amount]);
            } elseif ($type === 'liability' && $amount != 0) {
                $liabilities[] = array_merge($row, ['amount' => $amount]);
            } elseif ($type === 'equity' && $amount != 0) {
                $equity[] = array_merge($row, ['amount' => $amount]);
            }
        }

        return [
            'assets' => $assets,
            'total_assets' => collect($assets)->sum('amount'),
            'liabilities' => $liabilities,
            'total_liabilities' => collect($liabilities)->sum('amount'),
            'equity' => $equity,
            'total_equity' => collect($equity)->sum('amount'),
            'as_of_date' => $toDate ?? now()->toDateString(),
        ];
    }

    /**
     * صورت سود و زیان
     */
    public function getIncomeStatement(
        int $tenantId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $trial = $this->getTrialBalance($tenantId, $fromDate, $toDate);

        $revenues = [];
        $expenses = [];

        foreach ($trial['accounts'] as $row) {
            if ($row['type'] === 'revenue') {
                $amount = $row['credit'] - $row['debit'];
                if ($amount != 0) $revenues[] = array_merge($row, ['amount' => $amount]);
            } elseif ($row['type'] === 'expense') {
                $amount = $row['debit'] - $row['credit'];
                if ($amount != 0) $expenses[] = array_merge($row, ['amount' => $amount]);
            }
        }

        $totalRevenue = collect($revenues)->sum('amount');
        $totalExpense = collect($expenses)->sum('amount');

        return [
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expense' => $totalExpense,
            'net_profit' => $totalRevenue - $totalExpense,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }
}
