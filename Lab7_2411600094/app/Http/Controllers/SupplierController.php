<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Lab 7 Part 1 Step 5: the supplier master file module.
 *
 * Access is split the way Prompt 0 defines it — every action asks the
 * SupplierPolicy (registered in AppServiceProvider) through Gate::authorize(),
 * so a staff account never reaches a supplier screen at all, only admin and
 * manager may write, and only admin may retire or restore a supplier.
 */
class SupplierController extends Controller
{
    /** Sort pick-list: what the page offers, mapped to a real column. */
    private const SORTS = [
        'name'            => ['name', 'asc'],
        'lead_time'       => ['lead_time_days', 'asc'],
        'minimum_order'   => ['minimum_order_amount', 'desc'],
        'newest'          => ['created_at', 'desc'],
    ];

    /** Status tabs on the index page. */
    private const STATUSES = ['all', 'active', 'inactive', 'deleted'];

    /* ==================================================================
       index
       ================================================================== */

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Supplier::class);

        $status = in_array($request->input('status'), self::STATUSES, true)
            ? $request->input('status')
            : 'all';

        $suppliers = Supplier::query()
            ->when($status === 'deleted',  fn ($q) => $q->onlyTrashed())
            ->when($status === 'active',   fn ($q) => $q->active())
            ->when($status === 'inactive', fn ($q) => $q->inactive())
            ->search($request->input('search'))
            ->withCount('products')
            ->orderBy(...self::SORTS[$request->input('sort')] ?? self::SORTS['name'])
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'status'    => $status,
            'statuses'  => self::STATUSES,
            'sorts'     => array_keys(self::SORTS),
            'counts'    => [
                'all'      => Supplier::count(),
                'active'   => Supplier::active()->count(),
                'inactive' => Supplier::inactive()->count(),
                'deleted'  => Supplier::onlyTrashed()->count(),
            ],
        ]);
    }

    /* ==================================================================
       create / store
       ================================================================== */

    public function create()
    {
        Gate::authorize('create', Supplier::class);

        return view('suppliers.create', ['supplier' => null]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Supplier::class);

        $validated = $request->validate($this->rules(), $this->messages());

        $supplier = Supplier::create($validated);

        // Polymorphic audit trail (activity_logs) — Part 2 shows the same
        // stream for a purchase order's approval history.
        $supplier->logActivity('created', "Supplier \"{$supplier->name}\" was added to the master file.");

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', "Supplier \"{$supplier->name}\" added successfully.");
    }

    /* ==================================================================
       show
       ================================================================== */

    public function show(Supplier $supplier)
    {
        Gate::authorize('view', $supplier);

        return view('suppliers.show', [
            'supplier'   => $supplier->loadCount('products'),
            'products'   => $supplier->products()->with('category')->orderBy('name')->get(),
            // Price agreements, preferred first: the primary vendor plus the
            // alternatives Part 3 will choose between.
            'agreements' => $supplier->pricedProducts()->orderBy('name')->get(),
            'orders'     => $this->recentOrders($supplier),
            'activities' => $supplier->activities()->with('user')->take(10)->get(),
        ]);
    }

    /**
     * Lab 7 Part 2 Step 4: the last ten orders raised against this supplier,
     * soft-deleted ones included (a retired order is still history). Totals are
     * read straight from the header columns, so no line loading is needed here.
     */
    private function recentOrders(Supplier $supplier)
    {
        return $supplier->purchaseOrders()
                        ->withTrashed()
                        ->withCount('items')
                        ->latest('order_date')
                        ->latest('id')
                        ->take(10)
                        ->get();
    }

    /* ==================================================================
       edit / update
       ================================================================== */

    public function edit(Supplier $supplier)
    {
        Gate::authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        Gate::authorize('update', $supplier);

        $validated = $request->validate($this->rules($supplier), $this->messages());

        $changed = collect($validated)->diffAssoc(
            $supplier->only(array_keys($validated))
        )->keys()->all();

        $supplier->update($validated);

        if ($changed) {
            $supplier->logActivity(
                'updated',
                "Supplier \"{$supplier->name}\" was edited: " . implode(', ', $changed) . '.',
                ['fields' => $changed],
            );
        }

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', "Supplier \"{$supplier->name}\" updated successfully.");
    }

    /* ==================================================================
       destroy / restore  (soft delete — admin only)
       ================================================================== */

    public function destroy(Supplier $supplier)
    {
        Gate::authorize('delete', $supplier);

        // Lab 7 Part 2: a supplier in the middle of an order cannot be retired.
        if ($supplier->hasActivePurchaseOrders()) {
            return back()->with('error', sprintf(
                '"%s" still has active purchase orders. Cancel or receive them before deleting this supplier.',
                $supplier->name
            ));
        }

        $name = $supplier->name;
        $supplier->delete();
        $supplier->logActivity('deleted', "Supplier \"{$name}\" was retired (soft deleted).");

        return redirect()
            ->route('suppliers.index', ['status' => 'deleted'])
            ->with('success', "Supplier \"{$name}\" deleted — its records are kept, so it can be restored.");
    }

    /** Lab 6 pattern (products.restore): admin-only, route bound withTrashed(). */
    public function restore(Supplier $supplier)
    {
        Gate::authorize('restore', $supplier);
        abort_unless($supplier->trashed(), 404);

        $supplier->restore();
        $supplier->logActivity('restored', "Supplier \"{$supplier->name}\" was restored.");

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', "Supplier \"{$supplier->name}\" was restored.");
    }

    /* ==================================================================
       helpers
       ================================================================== */

    /** @return array<string, mixed> validation rules for store() and update() */
    private function rules(?Supplier $ignore = null): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'contact_person'     => ['required', 'string', 'max:255'],
            // Unique across the whole table, soft-deleted rows included: the
            // email rule in MySQL cannot see deleted_at, and neither do we.
            'email'              => ['required', 'string', 'email', 'max:255',
                                     Rule::unique('suppliers', 'email')->ignore($ignore?->id)],
            'phone'              => ['required', 'string', 'max:30',
                                     'regex:/^\+?[\d(][\d\s\-().]*\d$/',
                                     function (string $attribute, $value, callable $fail): void {
                                         $digits = preg_replace('/\D/', '', (string) $value);

                                         if (! preg_match('/^(?:0\d{9,10}|63\d{8,10})$/', $digits)) {
                                             $fail('Enter a Philippine number, e.g. 0917-555-2841 or (02) 8724-1188.');
                                         }
                                     }],
            'address'            => ['required', 'string', 'max:2000'],
            'payment_terms'      => ['required', 'string', Rule::in(Supplier::PAYMENT_TERMS)],
            'lead_time_days'     => ['required', 'integer', 'min:0', 'max:365'],
            'minimum_order_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active'          => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> plain-language errors, as ProductController does */
    private function messages(): array
    {
        return [
            'name.required'           => 'Please provide the supplier company name.',
            'contact_person.required' => 'Please name the person we deal with at this supplier.',
            'email.required'          => 'Please provide the e-mail address the purchase order is sent to.',
            'email.email'             => 'That does not look like an e-mail address, e.g. sales@supplier.ph.',
            'email.unique'            => 'That e-mail is already on file. If the supplier was deleted, '
                                         . 'open the Deleted list and restore it instead of adding it again.',
            'phone.required'          => 'Please provide a contact number.',
            'phone.regex'             => 'A phone number may only contain digits, spaces, dashes and brackets.',
            'address.required'        => 'Please provide the delivery address.',
            'payment_terms.required'  => 'Please choose the payment terms.',
            'payment_terms.in'        => 'Payment terms must be COD, Net 15, Net 30, Net 45 or Net 60.',
            'lead_time_days.required' => 'Please enter how many days delivery usually takes.',
            'lead_time_days.integer'  => 'Lead time must be a whole number of days.',
            'lead_time_days.min'      => 'Lead time cannot be negative.',
            'lead_time_days.max'      => 'Lead time cannot be more than a year.',
            'minimum_order_amount.required' => 'Please enter the supplier\'s minimum order amount (0 if none).',
            'minimum_order_amount.numeric'  => 'Minimum order amount must be a number, e.g. 15000.00.',
            'minimum_order_amount.min'      => 'Minimum order amount cannot be negative.',
        ];
    }
}
