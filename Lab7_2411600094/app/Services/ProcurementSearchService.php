<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProcurementSearchService
{
    public function purchaseOrders(Request $request): Builder
    {
        return PurchaseOrder::query()
            ->search($request->input('q'))
            ->byStatus($request->input('status'))
            ->forSupplier($request->integer('supplier_id') ?: null)
            ->dateBetween($request->input('from'), $request->input('to'));
    }

    public function suppliers(Request $request): Builder
    {
        $status = $request->input('status');
        $query = Supplier::query()->search($request->input('q'));

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $sort = in_array($request->input('sort'), [
            'name',
            'contact_person',
            'lead_time_days',
            'payment_terms',
            'created_at',
        ], true) ? $request->input('sort') : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction);
    }
}
