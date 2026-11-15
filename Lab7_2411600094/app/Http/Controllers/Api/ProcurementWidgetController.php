<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PendingOrdersResource;
use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\ReorderAlertResource;
use App\Http\Resources\SupplierPerformanceResource;
use App\Services\ProcurementMetricsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Lab 7 Part 5 Step 4: the JSON the dashboard poller reads for the four
 * procurement widgets.
 *
 * Shape follows the Lab 6 endpoints in this same group — `{ data, meta }`, flat
 * snake_case keys, so the JS stays dumb — and the numbers come from the very
 * same ProcurementMetricsService the view composers call. That is the point of
 * the exercise: a widget that polls an endpoint computing its own totals would
 * disagree with the card it is repainting the moment two rules drift apart.
 * Here the card and the JSON cannot, and a hit on the endpoint reuses the
 * cached rows the card paid for.
 *
 * Authorization is the same gate the sidebar and the dashboard section use, so
 * "staff never see procurement" is one rule with four entry points instead of
 * four rules. An unauthenticated call is stopped by the group's `auth`
 * middleware; an authorised-but-not-permitted one gets a 403 and, because the
 * poller sends `Accept: application/json`, Laravel renders that as JSON.
 */
class ProcurementWidgetController extends Controller
{
    public function __construct(private readonly ProcurementMetricsService $metrics)
    {
    }

    /** Orders waiting for a signature, and how much money that is. */
    public function pendingOrders(): PendingOrdersResource
    {
        Gate::authorize('view-procurement');

        $data = $this->metrics->pendingOrders();

        return (new PendingOrdersResource($data))
            ->additional(['meta' => ['last_updated' => $data['generated_at']]]);
    }

    /** The five most recent purchase orders. */
    public function recentOrders()
    {
        Gate::authorize('view-procurement');

        $payload = $this->metrics->recentOrders();

        return $this->collection(PurchaseOrderResource::collection($payload['rows']), $payload);
    }

    /** Top five suppliers by on-time delivery percentage. */
    public function supplierPerformance()
    {
        Gate::authorize('view-procurement');

        $payload = $this->metrics->supplierPerformance();

        return $this->collection(SupplierPerformanceResource::collection($payload['rows']), $payload);
    }

    /** The six most urgent short items, with what the engine would order. */
    public function reorderAlerts()
    {
        Gate::authorize('view-procurement');

        $payload = $this->metrics->reorderAlerts();

        return $this->collection(ReorderAlertResource::collection($payload['rows']), $payload);
    }

    /**
     * Every list answer carries the same meta block: when the cached rows were
     * built, and how many there are — the poller needs no other arithmetic to
     * tell "nothing to show" from "the request failed".
     */
    private function collection(AnonymousResourceCollection $collection, array $payload): AnonymousResourceCollection
    {
        return $collection->additional([
            'meta' => [
                'last_updated' => $payload['generated_at'],
                'count'        => count($payload['rows']),
            ],
        ]);
    }
}
