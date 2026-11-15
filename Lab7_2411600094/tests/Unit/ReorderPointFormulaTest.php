<?php

namespace Tests\Unit;

use App\Services\ReorderService;
use PHPUnit\Framework\TestCase;

/**
 * Lab 7 Part 3 Step 2: the reorder point formula, on its own.
 *
 * Plain PHPUnit rather than Tests\TestCase because the helper under test touches
 * no database and no container — that is exactly why Step 1 asked for
 * ReorderService::computeReorderPoint() as a pure static function: the one rule
 * the whole replenishment engine rests on can be proven without booting the
 * application or seeding anything.
 */
class ReorderPointFormulaTest extends TestCase
{
    /** The docx's worked example: 10 units/day x 7 days lead + 20 safety = 90. */
    public function test_the_docx_example(): void
    {
        $this->assertSame(90, ReorderService::computeReorderPoint(10, 7, 20));
    }

    /** Demand that does not land on a whole unit is rounded up, never down. */
    public function test_partial_units_are_rounded_up(): void
    {
        // 2.4 x 7 = 16.8 units of lead-time demand → 17, plus the buffer.
        $this->assertSame(27, ReorderService::computeReorderPoint(2.4, 7, 10));

        // 0.45 x 7 = 3.15 → 4, plus the buffer.
        $this->assertSame(7, ReorderService::computeReorderPoint(0.45, 7, 3));
    }

    /** An item with no recorded demand still protects its safety stock. */
    public function test_a_quiet_item_keeps_its_buffer(): void
    {
        $this->assertSame(25, ReorderService::computeReorderPoint(0, 14, 25));
    }

    /** Bad inputs must not produce a point that would order nothing forever. */
    public function test_negative_inputs_are_clamped(): void
    {
        $this->assertSame(0, ReorderService::computeReorderPoint(-10, -7, -20));
        $this->assertSame(20, ReorderService::computeReorderPoint(-10, 7, 20));
    }
}
