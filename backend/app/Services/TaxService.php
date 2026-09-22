<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TaxService
{
    protected bool $isMock;
    protected ?string $apiUrl;
    protected ?string $taxpayerId;
    protected ?string $privateKey;

    public function __construct()
    {
        $this->isMock = config('tax.mode') === 'mock';
        $this->apiUrl = config('tax.api_url');
        $this->taxpayerId = config('tax.taxpayer_id');

        $keyPath = config('tax.private_key_path');
        if ($keyPath && file_exists($keyPath)) {
            $this->privateKey = file_get_contents($keyPath);
        }
    }

    public function sendInvoice(Invoice $invoice): array
    {
        if ($invoice->status !== 'confirmed') {
            throw new InvalidArgumentException('فقط فاکتورهای تایید شده قابل ارسال به مودیان هستند.');
        }

        if (!in_array($invoice->type, ['sale', 'sale_return'], true)) {
            throw new InvalidArgumentException('فقط فاکتورهای فروش و برگشت از فروش قابل ارسال هستند.');
        }

        $payload = $this->buildInvoicePayload($invoice);

        if ($this->isMock) {
            return $this->mockResponse($invoice, $payload);
        }

        return $this->sendToApi($invoice, $payload);
    }

    public function buildInvoicePayload(Invoice $invoice): array
    {
        $invoice->load(['items.product.unit', 'party', 'tenant']);

        $items = $invoice->items->map(function (InvoiceItem $item, $index) {
            return [
                'rowNumber' => $index + 1,
                'invoiceRowId' => $item->id,
                'productCode' => $item->product?->code ?? '',
                'productName' => $item->product?->name ?? '',
                'barcode' => $item->product?->barcode ?? '',
                'quantity' => (float) $item->quantity,
                'unit' => $item->product?->unit?->symbol ?? 'عدد',
                'unitPrice' => (float) $item->unit_price,
                'discountAmount' => (float) $item->discount_amount,
                'taxRate' => (float) $item->tax_percent,
                'taxAmount' => (float) $item->tax_amount,
                'totalAmount' => (float) $item->total_amount,
                'itemDescription' => $item->description ?? '',
            ];
        })->toArray();

        return [
            'header' => [
                'taxpayerId' => $this->taxpayerId,
                'economicCode' => config('tax.seller_economic_code'),
                'invoiceNumber' => $invoice->number,
                'invoiceDate' => $invoice->issue_date?->format('Ymd'),
                'invoiceType' => $invoice->type === 'sale' ? 'SALE' : 'SALE_RETURN',
                'referenceNumber' => $invoice->reference_number,
                'sellerName' => $invoice->tenant->name ?? '',
                'sellerAddress' => $invoice->tenant->address ?? '',
                'sellerPhone' => $invoice->tenant->phone ?? '',
            ],
            'buyer' => [
                'name' => $invoice->party->name,
                'companyName' => $invoice->party->company_name,
                'economicCode' => $invoice->party->economic_code ?? $invoice->buyer_economic_code,
                'nationalId' => $invoice->party->national_id,
                'phone' => $invoice->party->phone ?? $invoice->party->mobile,
                'address' => $invoice->party->address,
            ],
            'items' => $items,
            'summary' => [
                'totalBeforeTax' => (float) $invoice->subtotal,
                'totalDiscount' => (float) $invoice->discount_amount,
                'totalTax' => (float) $invoice->tax_amount,
                'totalAmount' => (float) $invoice->total_amount,
                'totalItems' => count($items),
            ],
        ];
    }

    public function signPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $data = base64_encode($json);

        if (!$this->privateKey) {
            return base64_encode('MOCK_SIGNATURE_' . md5($data));
        }

        $privateKey = openssl_pkey_get_private($this->privateKey);
        if (!$privateKey) {
            throw new InvalidArgumentException('کلید خصوصی نامعتبر است.');
        }

        $signature = '';
        openssl_sign($json, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    protected function sendToApi(Invoice $invoice, array $payload): array
    {
        $signedPayload = $this->signPayload($payload);

        $request = [
            'header' => [
                'requestId' => (string) Str::uuid(),
                'timestamp' => now()->timestamp,
                'taxpayerId' => $this->taxpayerId,
            ],
            'body' => [
                'payload' => base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE)),
                'signature' => $signedPayload,
            ],
        ];

        try {
            $response = Http::timeout(config('tax.timeout'))
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->apiUrl}/invoice/submit", $request);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'خطا در ارتباط با سامانه مودیان',
                    'http_status' => $response->status(),
                ];
            }

            return [
                'success' => true,
                'response' => $response->json(),
                'tax_uid' => $response->json('result.taxUid'),
                'reference_id' => $response->json('result.referenceId'),
            ];
        } catch (\Exception $e) {
            Log::error('Tax API Error', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function mockResponse(Invoice $invoice, array $payload): array
    {
        $taxUid = 'MOCK-' . strtoupper(Str::random(16));
        $referenceId = 'REF-' . date('Ymd') . '-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT);

        Log::info('Tax Mock: Invoice submitted', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'tax_uid' => $taxUid,
        ]);

        return [
            'success' => true,
            'mock' => true,
            'tax_uid' => $taxUid,
            'reference_id' => $referenceId,
            'response' => [
                'result' => [
                    'taxUid' => $taxUid,
                    'referenceId' => $referenceId,
                    'status' => 'ACCEPTED',
                    'message' => 'فاکتور در حالت تست پذیرفته شد',
                ],
            ],
        ];
    }

    public function sendAndRecord(Invoice $invoice): array
    {
        $invoice->update(['tax_status' => 'pending']);
        $result = $this->sendInvoice($invoice);

        if ($result['success']) {
            $invoice->update([
                'tax_status' => 'sent',
                'tax_uid' => $result['tax_uid'] ?? null,
                'tax_reference_id' => $result['reference_id'] ?? null,
                'tax_sent_at' => now(),
                'tax_payload' => $result['response'] ?? null,
                'tax_error_message' => null,
            ]);
        } else {
            $invoice->update([
                'tax_status' => 'rejected',
                'tax_retry_count' => $invoice->tax_retry_count + 1,
                'tax_error_message' => $result['error'] ?? 'خطای نامشخص',
            ]);
        }

        return $result;
    }

    public function queryStatus(Invoice $invoice): array
    {
        if (!$invoice->tax_uid) {
            return ['success' => false, 'error' => 'این فاکتور شناسه مالیاتی ندارد.'];
        }

        if ($this->isMock) {
            return ['success' => true, 'status' => 'ACCEPTED', 'message' => 'حالت تست'];
        }

        try {
            $response = Http::timeout(config('tax.timeout'))
                ->get("{$this->apiUrl}/invoice/status/{$invoice->tax_uid}");

            return [
                'success' => $response->successful(),
                'status' => $response->json('result.status'),
                'response' => $response->json(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
