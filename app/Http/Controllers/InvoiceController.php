<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ScreeningRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('customer')->latest('issued_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'ilike', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%{$search}%"));
            });
        }

        $invoices = $query->paginate(25)->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('invoices.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'period_start'   => 'required|date',
            'period_end'     => 'required|date|after_or_equal:period_start',
            'issued_at'      => 'required|date',
            'due_at'         => 'required|date|after_or_equal:issued_at',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty'         => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'request_ids'    => 'nullable|string',
        ]);

        $start = Carbon::parse($data['period_start'])->startOfDay();
        $end   = Carbon::parse($data['period_end'])->endOfDay();

        if (Invoice::where('customer_id', $data['customer_id'])->where('period_date', $start->toDateString())->exists()) {
            return back()->withInput()->withErrors(['period_start' => 'An invoice for this customer starting on that date already exists.']);
        }

        $requestIds = collect(json_decode($data['request_ids'] ?? '[]', true))
            ->filter(fn ($id) => is_int($id))
            ->values();

        $invoice = null;

        DB::transaction(function () use ($data, $requestIds, $start, $end, &$invoice) {
            $subtotal = collect($data['items'])->sum(fn ($i) => $i['qty'] * $i['unit_price']);
            $tax      = round($subtotal * 0.06, 2);
            $total    = $subtotal + $tax;

            $invoice = Invoice::create([
                'customer_id' => $data['customer_id'],
                'number'      => Invoice::generateNumber(),
                'period'      => $this->formatPeriodDisplay($start, $end),
                'period_date' => $start->toDateString(),
                'period_end'  => $end->toDateString(),
                'status'      => 'unpaid',
                'issued_at'   => $data['issued_at'],
                'due_at'      => $data['due_at'],
                'subtotal'    => $subtotal,
                'tax'         => $tax,
                'total'       => $total,
            ]);

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item['description'],
                    'qty'         => $item['qty'],
                    'unit_price'  => $item['unit_price'],
                    'total'       => $item['qty'] * $item['unit_price'],
                ]);
            }

            if ($requestIds->isNotEmpty()) {
                ScreeningRequest::where('customer_id', $data['customer_id'])
                    ->whereIn('id', $requestIds)
                    ->whereNull('invoice_id')
                    ->update(['invoice_id' => $invoice->id]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items',
            'receipts.uploadedBy',
            'receipts.verifiedBy',
            'screeningRequests',
        ]);
        return view('invoices.show', compact('invoice'));
    }

    public function previewItems(Request $request)
    {
        $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $start = Carbon::parse($request->period_start)->startOfDay();
        $end   = Carbon::parse($request->period_end)->endOfDay();

        $result = $this->buildItemsForCustomer((int) $request->customer_id, $start, $end);

        return response()->json($result);
    }

    public function bulkGenerate()
    {
        return view('invoices.bulk-generate');
    }

    public function bulkPreview(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $start = Carbon::parse($request->period_start)->startOfDay();
        $end   = Carbon::parse($request->period_end)->endOfDay();

        $customers = Customer::whereHas('agreements', fn ($q) => $q->where('billing', '!=', 'per_request'))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Find existing invoices whose period overlaps the requested range for any of these customers
        $existingInvoices = Invoice::whereIn('customer_id', $customers->pluck('id'))
            ->where('period_date', '<=', $end->toDateString())
            ->where(fn ($q) => $q->whereNull('period_end')->orWhere('period_end', '>=', $start->toDateString()))
            ->get(['id', 'customer_id', 'number'])
            ->groupBy('customer_id');

        $rows    = [];
        $skipped = [];

        foreach ($customers as $customer) {
            if ($existingInvoices->has($customer->id)) {
                foreach ($existingInvoices[$customer->id] as $existing) {
                    $skipped[] = [
                        'customer_name'  => $customer->name,
                        'invoice_number' => $existing->number,
                        'invoice_id'     => $existing->id,
                    ];
                }
                continue;
            }

            $result = $this->buildItemsForCustomer($customer->id, $start, $end);
            if (empty($result['items'])) {
                continue;
            }

            $subtotal = collect($result['items'])->sum(fn ($i) => $i['qty'] * $i['unit_price']);
            $rows[] = [
                'customer_id'   => $customer->id,
                'customer_name' => $customer->name,
                'items'         => $result['items'],
                'request_ids'   => collect($result['requests'])->pluck('id')->values()->all(),
                'subtotal'      => round($subtotal, 2),
                'tax'           => round($subtotal * 0.06, 2),
                'total'         => round($subtotal * 1.06, 2),
            ];
        }

        return response()->json(['customers' => $rows, 'skipped' => $skipped]);
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'issued_at'                       => 'required|date',
            'due_at'                          => 'required|date|after_or_equal:issued_at',
            'period_start'                    => 'required|date',
            'period_end'                      => 'required|date|after_or_equal:period_start',
            'customers'                       => 'required|array|min:1',
            'customers.*.customer_id'         => 'required|exists:customers,id',
            'customers.*.items'               => 'required|array|min:1',
            'customers.*.items.*.description' => 'required|string',
            'customers.*.items.*.qty'         => 'required|integer|min:1',
            'customers.*.items.*.unit_price'  => 'required|numeric|min:0',
            'customers.*.request_ids'         => 'nullable|array',
        ]);

        $start         = Carbon::parse($data['period_start'])->startOfDay();
        $end           = Carbon::parse($data['period_end'])->endOfDay();
        $periodDisplay = $this->formatPeriodDisplay($start, $end);
        $created       = 0;

        DB::transaction(function () use ($data, $start, $end, $periodDisplay, &$created) {
            foreach ($data['customers'] as $row) {
                // Double-check for overlap before inserting
                $overlap = Invoice::where('customer_id', $row['customer_id'])
                    ->where('period_date', '<=', $end->toDateString())
                    ->where(fn ($q) => $q->whereNull('period_end')->orWhere('period_end', '>=', $start->toDateString()))
                    ->exists();
                if ($overlap) {
                    continue;
                }

                $subtotal = collect($row['items'])->sum(fn ($i) => $i['qty'] * $i['unit_price']);
                $tax      = round($subtotal * 0.06, 2);
                $total    = $subtotal + $tax;

                $invoice = Invoice::create([
                    'customer_id' => $row['customer_id'],
                    'number'      => Invoice::generateNumber(),
                    'period'      => $periodDisplay,
                    'period_date' => $start->toDateString(),
                    'period_end'  => $end->toDateString(),
                    'status'      => 'unpaid',
                    'issued_at'   => $data['issued_at'],
                    'due_at'      => $data['due_at'],
                    'subtotal'    => $subtotal,
                    'tax'         => $tax,
                    'total'       => $total,
                ]);

                foreach ($row['items'] as $item) {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'description' => $item['description'],
                        'qty'         => $item['qty'],
                        'unit_price'  => $item['unit_price'],
                        'total'       => $item['qty'] * $item['unit_price'],
                    ]);
                }

                $requestIds = collect($row['request_ids'] ?? [])->filter(fn ($id) => is_int($id) || ctype_digit((string) $id));
                if ($requestIds->isNotEmpty()) {
                    ScreeningRequest::where('customer_id', $row['customer_id'])
                        ->whereIn('id', $requestIds)
                        ->whereNull('invoice_id')
                        ->update(['invoice_id' => $invoice->id]);
                }

                $created++;
            }
        });

        return response()->json([
            'created'      => $created,
            'redirect_url' => route('invoices.index'),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->status !== 'unpaid') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only unpaid invoices can be edited.');
        }

        $invoice->load(['items', 'screeningRequests']);
        $linkedRequestIds = $invoice->screeningRequests->pluck('id')->values()->all();

        return view('invoices.edit', compact('invoice', 'linkedRequestIds'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'unpaid') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only unpaid invoices can be edited.');
        }

        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'issued_at'    => 'required|date',
            'due_at'       => 'required|date|after_or_equal:issued_at',
            'items'        => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty'         => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'request_ids'  => 'nullable|string',
        ]);

        $start = Carbon::parse($data['period_start'])->startOfDay();
        $end   = Carbon::parse($data['period_end'])->endOfDay();

        $conflict = Invoice::where('customer_id', $invoice->customer_id)
            ->where('id', '!=', $invoice->id)
            ->where('period_date', '<=', $end->toDateString())
            ->where(fn ($q) => $q->whereNull('period_end')->orWhere('period_end', '>=', $start->toDateString()))
            ->exists();

        if ($conflict) {
            return back()->withInput()->withErrors(['period_start' => 'Another invoice for this customer overlaps that date range.']);
        }

        $newRequestIds = collect(json_decode($data['request_ids'] ?? '[]', true))
            ->filter(fn ($id) => is_int($id))
            ->values();

        DB::transaction(function () use ($invoice, $data, $newRequestIds, $start, $end) {
            $subtotal = collect($data['items'])->sum(fn ($i) => $i['qty'] * $i['unit_price']);
            $tax      = round($subtotal * 0.06, 2);
            $total    = $subtotal + $tax;

            $invoice->update([
                'period'      => $this->formatPeriodDisplay($start, $end),
                'period_date' => $start->toDateString(),
                'period_end'  => $end->toDateString(),
                'issued_at'   => $data['issued_at'],
                'due_at'      => $data['due_at'],
                'subtotal'    => $subtotal,
                'tax'         => $tax,
                'total'       => $total,
            ]);

            $invoice->items()->delete();

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item['description'],
                    'qty'         => $item['qty'],
                    'unit_price'  => $item['unit_price'],
                    'total'       => $item['qty'] * $item['unit_price'],
                ]);
            }

            // Re-link requests: unlink old ones, link new selection
            ScreeningRequest::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);

            if ($newRequestIds->isNotEmpty()) {
                ScreeningRequest::where('customer_id', $invoice->customer_id)
                    ->whereIn('id', $newRequestIds)
                    ->whereNull('invoice_id')
                    ->update(['invoice_id' => $invoice->id]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid']);
        return back()->with('success', 'Invoice marked as paid.');
    }

    private function formatPeriodDisplay(Carbon $start, Carbon $end): string
    {
        if ($start->isSameMonth($end)) {
            return $start->format('d') . '–' . $end->format('d M Y');
        }
        return $start->format('d M Y') . ' – ' . $end->format('d M Y');
    }

    private function buildItemsForCustomer(int $customerId, Carbon $start, Carbon $end): array
    {
        $requests = ScreeningRequest::with(['candidates'])
            ->where('customer_id', $customerId)
            ->whereIn('status', ['in_progress', 'complete', 'updated'])
            ->whereNull('invoice_id')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        if ($requests->isEmpty()) {
            return ['items' => [], 'requests' => []];
        }

        $candidateIds = $requests->flatMap(fn ($r) => $r->candidates->pluck('id'));
        $scopeRows    = DB::table('candidate_scope_type')
            ->whereIn('request_candidate_id', $candidateIds)
            ->get(['request_candidate_id', 'scope_type_id']);

        $scopeIds = $scopeRows->pluck('scope_type_id')->unique()->values();

        $customerPrices = DB::table('customer_scope_prices')
            ->where('customer_id', $customerId)
            ->whereIn('scope_type_id', $scopeIds)
            ->pluck('price', 'scope_type_id');

        $scopeTypes = DB::table('scope_types')
            ->whereIn('id', $scopeIds)
            ->get(['id', 'name', 'price'])
            ->keyBy('id');

        $items = [];
        $requestList = [];

        foreach ($requests as $req) {
            $candidateIdsForReq = $req->candidates->pluck('id');
            $reqScopeIds = $scopeRows
                ->whereIn('request_candidate_id', $candidateIdsForReq->toArray())
                ->pluck('scope_type_id');

            $scopeCounts = $reqScopeIds->countBy();

            foreach ($scopeCounts as $scopeId => $count) {
                $scope = $scopeTypes[$scopeId] ?? null;
                $name  = $scope ? $scope->name : "Scope #{$scopeId}";
                $price = (float) ($customerPrices[$scopeId] ?? ($scope ? $scope->price : 0));

                $items[] = [
                    'description' => "{$name} – {$req->reference}",
                    'qty'         => $count,
                    'unit_price'  => number_format($price, 2, '.', ''),
                ];
            }

            $requestList[] = ['id' => $req->id, 'reference' => $req->reference];
        }

        return ['items' => $items, 'requests' => $requestList];
    }
}
