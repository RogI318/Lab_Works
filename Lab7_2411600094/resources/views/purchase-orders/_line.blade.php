@php
    /**
     * Lab 7 Part 2 Step 6: one editable line row.
     *
     * $index unique key for this row (item id when editing, a timestamp for a
     * new one, or the literal __INDEX__ inside the <template> the Add-line
     * button clones)
     * $line  PurchaseOrderItem|null
     */
@endphp

<tr>
    <td>
        <select name="items[{{ $index }}][product_id]"
                class="form-select form-select-sm"
                @if ($line) data-initial="{{ $line->product->name }} · {{ $line->product->sku }}" @endif
                required>
            @if ($line)
                <option value="{{ $line->product_id }}" selected>
                    {{ $line->product->name }} · {{ $line->product->sku }}
                </option>
            @else
                <option value="">-- choose a product --</option>
            @endif
        </select>
    </td>

    <td class="po-stock text-end small text-muted">
        {{ $line?->product ? number_format($line->product->quantity) : '' }}
    </td>

    <td>
        <input type="number" name="items[{{ $index }}][quantity]"
               class="form-control form-control-sm text-end"
               min="1" max="1000000" step="1"
               value="{{ $line?->quantity ?? 1 }}" required>
    </td>

    <td>
        <input type="number" name="items[{{ $index }}][unit_price]"
               class="form-control form-control-sm text-end"
               min="0" max="99999999.99" step="0.01"
               value="{{ $line?->unit_price }}"
               placeholder="0.00" required>
    </td>

    <td class="text-end fw-semibold po-line-total text-nowrap">
        PHP {{ $line ? number_format((float) $line->line_total, 2) : '0.00' }}
    </td>

    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger po-remove" title="Remove this line">
            <i class="bi bi-x-lg"></i>
        </button>
    </td>
</tr>
