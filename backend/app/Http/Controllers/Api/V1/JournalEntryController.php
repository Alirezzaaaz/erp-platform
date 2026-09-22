<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Accounting\StoreJournalEntryRequest;
use App\Http\Requests\Accounting\UpdateJournalEntryRequest;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JournalEntryController extends BaseApiController
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::query()
            ->where('tenant_id', $this->getTenantId())
            ->with(['lines.account', 'creator:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('from_date')) {
            $query->where('entry_date', '>=', $from);
        }

        if ($to = $request->input('to_date')) {
            $query->where('entry_date', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('entry_date', 'desc')->orderBy('id', 'desc');

        $perPage = min((int) $request->input('per_page', 30), 100);
        $entries = $query->paginate($perPage);

        return $this->collectionResponse(
            JournalEntryResource::collection($entries),
            'لیست اسناد دریافت شد.'
        );
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        try {
            $entry = $this->accountingService->createJournalEntry(
                tenantId: $this->getTenantId(),
                lines: $request->input('lines'),
                description: $request->input('description'),
                referenceType: $request->input('reference_type'),
                referenceId: $request->input('reference_id'),
                entryDate: $request->input('entry_date'),
                userId: $this->getUserId(),
                autoApprove: false
            );

            return $this->successResponse(
                new JournalEntryResource($entry),
                'سند با موفقیت ثبت شد.',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeEntry($journalEntry);
        $journalEntry->load(['lines.account', 'creator', 'approver', 'fiscalYear']);

        return $this->successResponse(
            new JournalEntryResource($journalEntry),
            'جزئیات سند دریافت شد.'
        );
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeEntry($journalEntry);

        if ($journalEntry->status === 'approved') {
            return $this->errorResponse('سند تایید شده قابل ویرایش نیست. ابتدا آن را لغو کنید.', 422);
        }

        DB::transaction(function () use ($request, $journalEntry) {
            if ($request->has('entry_date')) {
                $journalEntry->entry_date = $request->input('entry_date');
            }
            if ($request->has('description')) {
                $journalEntry->description = $request->input('description');
            }
            $journalEntry->save();

            // اگر ردیف‌های جدید داده شده
            if ($request->has('lines')) {
                $lines = $request->input('lines');

                // بررسی تراز
                $totalDebit = collect($lines)->sum('debit');
                $totalCredit = collect($lines)->sum('credit');
                if (abs($totalDebit - $totalCredit) > 0.01) {
                    throw new InvalidArgumentException('سند تراز نیست.');
                }

                $journalEntry->lines()->delete();

                foreach ($lines as $index => $line) {
                    $accountId = $line['account_id'] ?? null;
                    if (!$accountId && !empty($line['account_code'])) {
                        $accountId = \App\Models\Account::where('tenant_id', $journalEntry->tenant_id)
                            ->where('code', $line['account_code'])
                            ->value('id');
                    }

                    if (!$accountId) {
                        throw new InvalidArgumentException('حساب ردیف ' . ($index + 1) . ' یافت نشد.');
                    }

                    $journalEntry->lines()->create([
                        'account_id' => $accountId,
                        'debit' => $line['debit'] ?? 0,
                        'credit' => $line['credit'] ?? 0,
                        'description' => $line['description'] ?? null,
                        'row_order' => $index,
                    ]);
                }
            }
        });

        $journalEntry->refresh()->load(['lines.account', 'creator']);

        return $this->successResponse(
            new JournalEntryResource($journalEntry),
            'سند با موفقیت ویرایش شد.'
        );
    }

    /**
     * تایید سند
     */
    public function approve(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeEntry($journalEntry);

        if ($journalEntry->status === 'approved') {
            return $this->errorResponse('سند قبلاً تایید شده است.', 422);
        }

        if (!$journalEntry->isBalanced()) {
            return $this->errorResponse('سند تراز نیست و قابل تایید نمی‌باشد.', 422);
        }

        $journalEntry->update([
            'status' => 'approved',
            'approved_by' => $this->getUserId(),
            'approved_at' => now(),
        ]);

        return $this->successResponse(
            new JournalEntryResource($journalEntry->fresh(['lines.account'])),
            'سند با موفقیت تایید شد.'
        );
    }

    /**
     * لغو سند
     */
    public function cancel(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeEntry($journalEntry);

        if ($journalEntry->status === 'cancelled') {
            return $this->errorResponse('سند قبلاً لغو شده است.', 422);
        }

        $journalEntry->update(['status' => 'cancelled']);

        return $this->successResponse(
            new JournalEntryResource($journalEntry->fresh(['lines.account'])),
            'سند با موفقیت لغو شد.'
        );
    }

    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeEntry($journalEntry);

        if ($journalEntry->status === 'approved') {
            return $this->errorResponse('سند تایید شده قابل حذف نیست.', 422);
        }

        $journalEntry->lines()->delete();
        $journalEntry->delete();

        return $this->successResponse(null, 'سند با موفقیت حذف شد.');
    }

    protected function authorizeEntry(JournalEntry $entry): void
    {
        if ($entry->tenant_id !== $this->getTenantId()) {
            abort(404, 'سند یافت نشد.');
        }
    }
}
