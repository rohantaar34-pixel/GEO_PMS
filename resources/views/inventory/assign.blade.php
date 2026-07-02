{{-- resources/views/inventory/assign.blade.php --}}
@extends('layouts.app')

@section('content')
<style>
    :root {
        --inv: #0891b2; --inv-dark: #0e7490; --inv-light: #ecfeff;
        --green: #059669; --red: #dc2626;
        --ink: #111827; --ink-3: #6b7280; --ink-4: #9ca3af;
        --border: #e8e8ed; --white: #ffffff; --bg: #f8f8fb;
        --radius: 14px; --radius-sm: 9px;
    }
    * { box-sizing: border-box; }
    body { font-family: 'Montserrat', sans-serif; }

    .assign-wrap { max-width: 560px; margin: 0 auto; padding: 24px 20px 60px; }

    .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--inv); font-size: 13px; font-weight: 600; text-decoration: none; margin-bottom: 24px; }
    .back-link:hover { text-decoration: underline; }

    /* Item summary card */
    .item-summary { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; display: flex; gap: 16px; align-items: center; margin-bottom: 24px; }
    .item-icon { width: 52px; height: 52px; background: var(--inv-light); border-radius: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .item-name { font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 4px; }
    .item-meta { font-size: 13px; color: var(--ink-3); }
    .item-stock { font-size: 22px; font-weight: 800; color: var(--inv); }
    .item-stock-lbl { font-size: 11px; color: var(--ink-4); text-align: center; }

    /* Form card */
    .assign-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
    .assign-card-title { font-size: 16px; font-weight: 800; color: var(--ink); margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border); }

    .field-group { margin-bottom: 16px; }
    .field-lbl { display: block; font-size: 12px; font-weight: 600; color: var(--ink-3); margin-bottom: 5px; }
    .field-in { width: 100%; padding: 11px 14px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: var(--radius-sm); background: #fafafa; color: var(--ink); outline: none; transition: border-color .15s; font-family: 'Montserrat', sans-serif; }
    .field-in:focus { border-color: var(--inv); background: #fff; }
    select.field-in { cursor: pointer; }
    .expense-options { display: grid; gap: 8px; border: 0; padding: 0; margin: 0; }
    .expense-option { display: grid; grid-template-columns: 20px 1fr; gap: 10px; align-items: start; min-height: 52px; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: var(--radius-sm); background: #fff; cursor: pointer; }
    .expense-option:has(input:checked) { border-color: var(--inv); background: var(--inv-light); }
    .expense-option input { width: 18px; height: 18px; margin: 2px 0 0; accent-color: var(--inv); }
    .expense-option strong { display: block; color: var(--ink); font-size: 13px; margin-bottom: 2px; }
    .expense-option span { display: block; color: var(--ink-3); font-size: 12px; line-height: 1.45; }

    /* Live preview */
    .preview-box { background: #ecfeff; border: 1px solid #a5f3fc; border-radius: var(--radius-sm); padding: 16px 18px; margin: 16px 0; display: none; }
    .preview-box.show { display: block; }
    .preview-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; padding: 4px 0; }
    .preview-row:not(:last-child) { border-bottom: 1px solid #a5f3fc; }
    .preview-lbl { color: var(--ink-3); }
    .preview-val { font-weight: 700; color: var(--ink); }
    .preview-deduct { font-size: 18px; font-weight: 800; color: var(--red); }
    .preview-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--inv); margin-bottom: 10px; }

    /* Warning */
    .warn-box { background: #fef3c7; border: 1px solid #fcd34d; border-radius: var(--radius-sm); padding: 12px 14px; font-size: 13px; color: #78350f; margin-bottom: 16px; display: flex; gap: 8px; align-items: flex-start; }

    .btn-assign-submit { width: 100%; padding: 14px; background: var(--inv); color: #fff; font-size: 15px; font-weight: 800; border: none; border-radius: var(--radius-sm); cursor: pointer; transition: background .15s; font-family: 'Montserrat', sans-serif; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-assign-submit:hover { background: var(--inv-dark); }
    .btn-assign-submit:disabled { background: #9ca3af; cursor: not-allowed; }
</style>

<div class="assign-wrap">
    <a href="{{ route('inventory.index') }}" class="back-link">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Inventory
    </a>

    {{-- Item Summary --}}
    <div class="item-summary">
        <div class="item-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--inv)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
        </div>
        <div style="flex:1">
            <div class="item-name">{{ $inventory->name }}</div>
            <div class="item-meta">
                @if($inventory->category) {{ $inventory->category }} · @endif
                ₱{{ number_format($inventory->unit_cost, 2) }} / {{ $inventory->unit }}
                @if($inventory->description) · {{ $inventory->description }} @endif
            </div>
        </div>
        <div style="text-align:center;">
            <div class="item-stock">{{ number_format($inventory->quantity) }}</div>
            <div class="item-stock-lbl">{{ $inventory->unit }} available</div>
        </div>
    </div>

    {{-- Assign Form --}}
    <div class="assign-card">
        <div class="assign-card-title">
            🎯 Assign to Project
        </div>

        <div class="warn-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span id="assignmentEffect">Choose whether this assignment should affect the project budget.</span>
        </div>

        <form method="POST" action="{{ route('inventory.doAssign', $inventory) }}" id="assignForm">
            @csrf

            <div class="field-group">
                <fieldset class="expense-options">
                    <legend class="field-lbl">Record as Project Expense? *</legend>
                    <label class="expense-option">
                        <input type="radio" name="charge_to_project" value="1" required
                            @checked(old('charge_to_project') === '1') onchange="updatePreview()">
                        <span>
                            <strong>Yes, charge the project</strong>
                            <span>Create a ledger expense and reduce the selected project's available budget.</span>
                        </span>
                    </label>
                    <label class="expense-option">
                        <input type="radio" name="charge_to_project" value="0" required
                            @checked(old('charge_to_project') === '0') onchange="updatePreview()">
                        <span>
                            <strong>No, assign stock only</strong>
                            <span>Deduct inventory stock without changing the project budget or expense ledger.</span>
                        </span>
                    </label>
                </fieldset>
            </div>

            <div class="field-group">
                <label class="field-lbl" for="project_id">Target Project *</label>
                <select class="field-in" name="project_id" id="project_id" required onchange="updatePreview()">
                    <option value="">— Select a project —</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}"
                            data-balance="{{ $project->current_budget }}"
                            {{ old('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }} — Balance: ₱{{ number_format($project->current_budget, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field-group">
                <label class="field-lbl" for="quantity_assigned">Quantity to Assign * (max: {{ $inventory->quantity }} {{ $inventory->unit }})</label>
                <input class="field-in" type="number" name="quantity_assigned" id="quantity_assigned"
                    min="1" max="{{ $inventory->quantity }}" required
                    placeholder="Enter quantity"
                    value="{{ old('quantity_assigned') }}"
                    oninput="updatePreview()">
            </div>

            <div class="field-group">
                <label class="field-lbl" for="notes">Notes (optional)</label>
                <input class="field-in" type="text" name="notes" id="notes"
                    placeholder="e.g. For Phase 1 construction"
                    value="{{ old('notes') }}">
            </div>

            {{-- Live Preview --}}
            <div class="preview-box" id="previewBox">
                <div class="preview-title" id="previewTitle">Assignment Preview</div>
                <div class="preview-row" id="unitCostRow">
                    <span class="preview-lbl">Unit Cost</span>
                    <span class="preview-val">₱{{ number_format($inventory->unit_cost, 2) }} / {{ $inventory->unit }}</span>
                </div>
                <div class="preview-row">
                    <span class="preview-lbl">Quantity</span>
                    <span class="preview-val" id="prev-qty">—</span>
                </div>
                <div class="preview-row">
                    <span class="preview-lbl">Remaining Stock After</span>
                    <span class="preview-val" id="prev-stock">—</span>
                </div>
                <div class="preview-row">
                    <span class="preview-lbl">Project Balance After</span>
                    <span class="preview-val" id="prev-balance">—</span>
                </div>
                <div class="preview-row" style="border-top:2px solid #a5f3fc;margin-top:6px;padding-top:8px;">
                    <span class="preview-lbl" id="totalLabel" style="font-weight:700;">Budget Deduction</span>
                    <span class="preview-deduct" id="prev-total">₱0.00</span>
                </div>
            </div>

            <button type="submit" class="btn-assign-submit" id="submitBtn" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
                <span id="submitLabel">Choose Assignment Type</span>
            </button>
        </form>
    </div>
</div>

<script>
const unitCost = {{ $inventory->unit_cost }};
const maxQty   = {{ $inventory->quantity }};

function fmt(n) {
    return '₱' + parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updatePreview() {
    const qty = parseInt(document.getElementById('quantity_assigned').value) || 0;
    const sel = document.getElementById('project_id');
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('previewBox');
    const btn = document.getElementById('submitBtn');
    const expenseChoice = document.querySelector('input[name="charge_to_project"]:checked');
    const isExpense = expenseChoice?.value === '1';
    const hasChoice = Boolean(expenseChoice);
    const isValid = qty > 0 && qty <= maxQty && Boolean(sel.value) && hasChoice;

    document.getElementById('assignmentEffect').textContent = !hasChoice
        ? 'Choose whether this assignment should affect the project budget.'
        : (isExpense
            ? 'This assignment will create an expense and reduce the selected project budget.'
            : 'This assignment will only deduct inventory stock. Project budget and expenses will stay unchanged.');
    document.getElementById('submitLabel').textContent = !hasChoice
        ? 'Choose Assignment Type'
        : (isExpense ? 'Assign and Record Expense' : 'Assign Without Expense');
    btn.disabled = !isValid;

    if (isValid) {
        const balance   = parseFloat(opt.dataset.balance) || 0;
        const total     = isExpense ? qty * unitCost : 0;
        const newStock  = maxQty - qty;
        const newBal    = balance - total;

        document.getElementById('prev-qty').textContent     = qty + ' {{ $inventory->unit }}';
        document.getElementById('prev-stock').textContent   = newStock + ' {{ $inventory->unit }}' + (newStock <= 0 ? ' ⚠️' : '');
        document.getElementById('prev-balance').textContent = fmt(newBal) + (newBal < 0 ? ' ⚠️ Deficit!' : '');
        document.getElementById('prev-total').textContent   = fmt(total);
        document.getElementById('prev-balance').style.color = newBal < 0 ? '#dc2626' : '#059669';
        document.getElementById('unitCostRow').style.display = isExpense ? 'flex' : 'none';
        document.getElementById('previewTitle').textContent = isExpense ? 'Expense Assignment Preview' : 'Stock-Only Assignment Preview';
        document.getElementById('prev-total').style.color = isExpense ? '#dc2626' : '#059669';

        box.classList.add('show');
    } else {
        box.classList.remove('show');
    }
}

updatePreview();
</script>
@endsection
