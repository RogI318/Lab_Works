<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lab 7 Part 5 Step 4: the on-time aggregate row behind the performance widget.
 *
 * bar_class travels with the data so the polled JSON paints the same green or
 * amber the server-rendered card already showed.
 */
class SupplierPerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => (int) $this['id'],
            'name'      => $this['name'],
            'url'       => $this['url'],
            'received'  => (int) $this['received'],
            'on_time'   => (int) $this['on_time'],
            'percent'   => (float) $this['percent'],
            'bar_class' => $this['bar_class'],
        ];
    }
}
