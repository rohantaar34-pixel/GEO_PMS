<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialRequestController extends Controller
{
    public function create()
    {
        Gate::authorize('create', MaterialRequest::class);

        $user = Auth::user();
        $projects = $user->assignedProjects()->orderBy('name')->get();
        $items = InventoryItem::orderBy('name')->get();
        $categories = InventoryItem::categoryOptions();
        $requests = MaterialRequest::with(['project', 'reviewer', 'inventoryItem'])
            ->where('user_id', $user->id)
            ->latest('date_requested')
            ->latest()
            ->get();

        return view('material_requests.create', compact('projects', 'items', 'categories', 'requests'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', MaterialRequest::class);

        $user = Auth::user();
        $assignedProjectIds = $user->assignedProjects()->pluck('projects.id')->all();

        $validated = $request->validate([
            'project_id' => ['required', Rule::in($assignedProjectIds)],
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'material_name' => ['nullable', 'string', 'max:255', 'required_without:inventory_item_id'],
            'material_category' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:50'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'request_note' => ['nullable', 'string', 'max:2000'],
            'date_requested' => ['required', 'date'],
        ]);

        $item = !empty($validated['inventory_item_id'])
            ? InventoryItem::findOrFail($validated['inventory_item_id'])
            : null;

        $materialRequest = MaterialRequest::create([
            'request_number' => $this->nextRequestNumber(),
            'project_id' => $validated['project_id'],
            'user_id' => $user->id,
            'inventory_item_id' => $item?->id,
            'material_name' => $item?->name ?? $validated['material_name'],
            'material_category' => $item?->category ?? ($validated['material_category'] ?? null),
            'quantity' => $validated['quantity'],
            'unit' => $item?->unit ?? $validated['unit'],
            'purpose' => ($validated['purpose'] ?? null) ?: $item?->description,
            'request_note' => $validated['request_note'] ?? null,
            'date_requested' => $validated['date_requested'],
            'status' => MaterialRequest::STATUS_PENDING,
        ]);

        $this->audit($materialRequest, 'request_created', $validated['quantity']);

        return redirect()
            ->route('material-requests.create')
            ->with('success', 'Material request ' . $materialRequest->request_number . ' submitted for review.');
    }

    public function employeePulse()
    {
        Gate::authorize('create', MaterialRequest::class);

        $user = Auth::user();
        $assignedProjectIds = $user->assignedProjects()->pluck('projects.id');
        $requests = MaterialRequest::where('user_id', $user->id);

        $payload = [
            'assigned_projects' => $assignedProjectIds->sort()->values()->all(),
            'requests' => (clone $requests)->count(),
            'pending' => (clone $requests)->where('status', MaterialRequest::STATUS_PENDING)->count(),
            'waiting_procurement' => (clone $requests)->where('status', MaterialRequest::STATUS_WAITING_PROCUREMENT)->count(),
            'issued' => (clone $requests)->where('status', MaterialRequest::STATUS_ISSUED)->count(),
            'latest_request_update' => (string) (clone $requests)->max('updated_at'),
            'latest_inventory_update' => (string) InventoryItem::max('updated_at'),
        ];

        return response()->json([
            'signature' => sha1(json_encode($payload)),
            'checked_at' => now()->toIso8601String(),
            'pending' => $payload['pending'],
            'total' => $payload['requests'],
        ]);
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', MaterialRequest::class);

        $query = MaterialRequest::with(['project', 'requester', 'reviewer', 'inventoryItem'])
            ->latest('date_requested')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('request_number', 'like', "%{$search}%")
                    ->orWhere('material_name', 'like', "%{$search}%")
                    ->orWhereHas('requester', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->integer('employee_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_requested', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_requested', '<=', $request->date('date_to'));
        }

        $requests = $query->paginate(15)->withQueryString();
        $projects = Project::orderBy('name')->get();
        $employees = User::where('role', 'employee')->orderBy('name')->get();
        $categories = InventoryItem::categoryOptions();
        $statuses = $this->statuses();

        return view('material_requests.index', compact('requests', 'projects', 'employees', 'categories', 'statuses'));
    }

    public function reviewPulse()
    {
        Gate::authorize('viewAny', MaterialRequest::class);

        $payload = [
            'requests' => MaterialRequest::count(),
            'pending' => MaterialRequest::where('status', MaterialRequest::STATUS_PENDING)->count(),
            'waiting_procurement' => MaterialRequest::where('status', MaterialRequest::STATUS_WAITING_PROCUREMENT)->count(),
            'partially_approved' => MaterialRequest::where('status', MaterialRequest::STATUS_PARTIALLY_APPROVED)->count(),
            'issued' => MaterialRequest::where('status', MaterialRequest::STATUS_ISSUED)->count(),
            'rejected' => MaterialRequest::where('status', MaterialRequest::STATUS_REJECTED)->count(),
            'latest_request_update' => (string) MaterialRequest::max('updated_at'),
            'latest_inventory_update' => (string) InventoryItem::max('updated_at'),
            'inventory_quantity_sum' => InventoryItem::sum('quantity'),
        ];

        return response()->json([
            'signature' => sha1(json_encode($payload)),
            'checked_at' => now()->toIso8601String(),
            'pending' => $payload['pending'],
            'waiting_procurement' => $payload['waiting_procurement'],
            'total' => $payload['requests'],
        ]);
    }

    public function show(MaterialRequest $materialRequest)
    {
        Gate::authorize('view', $materialRequest);

        $materialRequest->load(['project', 'requester', 'reviewer', 'inventoryItem', 'audits.user']);

        return view('material_requests.show', compact('materialRequest'));
    }

    public function approve(Request $request, MaterialRequest $materialRequest)
    {
        Gate::authorize('approve', $materialRequest);

        $previouslyIssued = (int) ($materialRequest->approved_quantity ?? 0);
        $remainingQuantity = max((int) $materialRequest->quantity - $previouslyIssued, 0);

        if ($remainingQuantity === 0) {
            throw ValidationException::withMessages([
                'approved_quantity' => 'This request has already been issued in full.',
            ]);
        }

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['issue', 'partial', 'waiting', 'procure_issue'])],
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'approved_quantity' => ['nullable', 'integer', 'min:1', 'max:' . $remainingQuantity],
            'procurement_note' => ['nullable', 'string', 'max:2000'],
            'estimated_unit_cost' => ['nullable', 'numeric', 'min:0.01'],
            'inventory_name' => ['nullable', 'string', 'max:255', 'required_if:decision,procure_issue'],
            'inventory_category' => ['nullable', 'string', 'max:255'],
            'inventory_unit' => ['nullable', 'string', 'max:50', 'required_if:decision,procure_issue'],
            'inventory_description' => ['nullable', 'string', 'max:2000'],
        ]);

        $hasInventoryItem = $materialRequest->inventory_item_id || !empty($validated['inventory_item_id']);

        if (!in_array($validated['decision'], ['waiting', 'procure_issue'], true) && !$hasInventoryItem) {
            throw ValidationException::withMessages([
                'inventory_item_id' => 'Link an inventory item or choose Procured and issue for a new material.',
            ]);
        }

        $selectedItem = !empty($validated['inventory_item_id'])
            ? InventoryItem::find($validated['inventory_item_id'])
            : $materialRequest->inventoryItem;

        if (
            $validated['decision'] === 'procure_issue'
            && $selectedItem
            && (int) $selectedItem->quantity > 0
        ) {
            throw ValidationException::withMessages([
                'decision' => 'This inventory item already has stock and can be issued directly.',
            ]);
        }

        $estimatedUnitCost = (float) (
            $validated['estimated_unit_cost']
            ?? $selectedItem?->unit_cost
            ?? $materialRequest->estimated_unit_cost
            ?? 0
        );

        if (in_array($validated['decision'], ['waiting', 'procure_issue'], true) && $estimatedUnitCost <= 0) {
            throw ValidationException::withMessages([
                'estimated_unit_cost' => 'Enter the unit cost for this procurement.',
            ]);
        }

        DB::transaction(function () use ($validated, $materialRequest) {
            if (!empty($validated['inventory_item_id'])) {
                $materialRequest->update(['inventory_item_id' => $validated['inventory_item_id']]);
                $materialRequest->refresh();
            }

            $item = $materialRequest->inventoryItem;
            $decision = $validated['decision'];
            $previouslyIssued = (int) ($materialRequest->approved_quantity ?? 0);
            $remainingQuantity = max((int) $materialRequest->quantity - $previouslyIssued, 0);
            $approvedQuantity = (int) ($validated['approved_quantity'] ?? $remainingQuantity);
            $existingActualCost = (float) ($materialRequest->actual_total_cost ?? 0);
            $estimatedUnitCost = (float) (
                $validated['estimated_unit_cost']
                ?? $item?->unit_cost
                ?? $materialRequest->estimated_unit_cost
                ?? 0
            );

            if ($decision === 'procure_issue') {
                if (!$item) {
                    $item = InventoryItem::create([
                        'name' => $validated['inventory_name'],
                        'category' => $validated['inventory_category'] ?? $materialRequest->material_category,
                        'unit' => $validated['inventory_unit'],
                        'unit_cost' => $estimatedUnitCost,
                        'quantity' => $remainingQuantity,
                        'description' => $validated['inventory_description'] ?? $materialRequest->purpose,
                    ]);
                    $this->audit($materialRequest, 'inventory_item_created', $remainingQuantity, 'Created inventory item for procured request.');
                } else {
                    $item->update([
                        'name' => $validated['inventory_name'],
                        'category' => $validated['inventory_category'] ?? $item->category,
                        'unit' => $validated['inventory_unit'],
                        'unit_cost' => $estimatedUnitCost,
                        'description' => $validated['inventory_description'] ?? $item->description,
                    ]);
                    $item->increment('quantity', $remainingQuantity);
                    $item->refresh();
                    $this->audit($materialRequest, 'inventory_restocked', $remainingQuantity, 'Procured stock added to the linked inventory item.');
                }

                $materialRequest->update([
                    'inventory_item_id' => $item->id,
                    'material_name' => $item->name,
                    'material_category' => $item->category,
                    'unit' => $item->unit,
                    'purpose' => $item->description,
                ]);
                $materialRequest->setRelation('inventoryItem', $item);
            }

            if (!$item || $decision === 'waiting') {
                $estimatedTotal = $remainingQuantity * $estimatedUnitCost;

                $materialRequest->update([
                    'status' => MaterialRequest::STATUS_WAITING_PROCUREMENT,
                    'approved_quantity' => $previouslyIssued ?: null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'procurement_note' => $validated['procurement_note'] ?? 'Material requires procurement before issuance.',
                    'estimated_unit_cost' => $estimatedUnitCost,
                    'estimated_total_cost' => $estimatedTotal,
                    'budget_commitment_status' => MaterialRequest::COMMITMENT_RESERVED,
                ]);
                $this->audit($materialRequest, 'request_approved_waiting_procurement', $materialRequest->quantity, $materialRequest->procurement_note);
                $this->audit($materialRequest, 'budget_committed', $remainingQuantity, 'Reserved project budget: PHP ' . number_format($estimatedTotal, 2));
                return;
            }

            if ($decision === 'partial') {
                $approvedQuantity = min($approvedQuantity, $item->quantity, $remainingQuantity);

                if ($approvedQuantity <= 0) {
                    $estimatedTotal = $remainingQuantity * (float) $item->unit_cost;

                    $materialRequest->update([
                        'status' => MaterialRequest::STATUS_WAITING_PROCUREMENT,
                        'approved_quantity' => $previouslyIssued ?: null,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'procurement_note' => 'No stock available for partial issuance.',
                        'estimated_unit_cost' => $item->unit_cost,
                        'estimated_total_cost' => $estimatedTotal,
                        'budget_commitment_status' => MaterialRequest::COMMITMENT_RESERVED,
                    ]);
                    $this->audit($materialRequest, 'request_approved_waiting_procurement', $materialRequest->quantity, $materialRequest->procurement_note);
                    $this->audit($materialRequest, 'budget_committed', $remainingQuantity, 'Reserved project budget: PHP ' . number_format($estimatedTotal, 2));
                    return;
                }

                $transaction = $this->issueInventory($materialRequest, $item, $approvedQuantity);
                $totalIssued = $previouslyIssued + $approvedQuantity;
                $remainingAfterIssue = max((int) $materialRequest->quantity - $totalIssued, 0);
                $remainingEstimate = $remainingAfterIssue * (float) $item->unit_cost;
                $actualTotal = $existingActualCost + (float) $transaction->amount;

                $materialRequest->update([
                    'status' => $remainingAfterIssue > 0
                        ? MaterialRequest::STATUS_PARTIALLY_APPROVED
                        : MaterialRequest::STATUS_ISSUED,
                    'approved_quantity' => $totalIssued,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'procurement_note' => $validated['procurement_note'] ?? null,
                    'estimated_unit_cost' => $item->unit_cost,
                    'estimated_total_cost' => $remainingAfterIssue > 0 ? $remainingEstimate : $materialRequest->estimated_total_cost,
                    'actual_total_cost' => $actualTotal,
                    'budget_commitment_status' => $remainingAfterIssue > 0
                        ? MaterialRequest::COMMITMENT_RESERVED
                        : MaterialRequest::COMMITMENT_CONVERTED,
                    'budget_transaction_id' => $transaction->id,
                ]);
                $this->audit($materialRequest, 'request_partially_approved', $approvedQuantity);
                $this->audit($materialRequest, 'inventory_deducted', $approvedQuantity);
                if ($remainingAfterIssue > 0) {
                    $this->audit($materialRequest, 'budget_committed', $remainingAfterIssue, 'Reserved remaining project budget: PHP ' . number_format($remainingEstimate, 2));
                } else {
                    $this->audit($materialRequest, 'budget_commitment_converted', $approvedQuantity, 'Reservation converted to actual expense.');
                }
                return;
            }

            if ($item->quantity < $remainingQuantity) {
                $estimatedTotal = $remainingQuantity * (float) $item->unit_cost;

                $materialRequest->update([
                    'status' => MaterialRequest::STATUS_WAITING_PROCUREMENT,
                    'approved_quantity' => $previouslyIssued ?: null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'procurement_note' => $validated['procurement_note'] ?? 'Stock is insufficient. Waiting for procurement.',
                    'estimated_unit_cost' => $item->unit_cost,
                    'estimated_total_cost' => $estimatedTotal,
                    'budget_commitment_status' => MaterialRequest::COMMITMENT_RESERVED,
                ]);
                $this->audit($materialRequest, 'request_approved_waiting_procurement', $materialRequest->quantity, $materialRequest->procurement_note);
                $this->audit($materialRequest, 'budget_committed', $remainingQuantity, 'Reserved project budget: PHP ' . number_format($estimatedTotal, 2));
                return;
            }

            $transaction = $this->issueInventory($materialRequest, $item, $remainingQuantity);
            $materialRequest->update([
                'status' => MaterialRequest::STATUS_ISSUED,
                'approved_quantity' => $materialRequest->quantity,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'procurement_note' => $validated['procurement_note'] ?? $materialRequest->procurement_note,
                'actual_total_cost' => $existingActualCost + (float) $transaction->amount,
                'estimated_unit_cost' => $materialRequest->estimated_unit_cost ?? $item->unit_cost,
                'estimated_total_cost' => $materialRequest->estimated_total_cost ?? (float) $transaction->amount,
                'budget_commitment_status' => MaterialRequest::COMMITMENT_CONVERTED,
                'budget_transaction_id' => $transaction->id,
            ]);
            $this->audit($materialRequest, 'request_approved', $remainingQuantity);
            $this->audit($materialRequest, 'inventory_deducted', $remainingQuantity);
            $this->audit($materialRequest, 'budget_commitment_converted', $remainingQuantity, 'Reservation converted to actual expense.');
        });

        return redirect()
            ->route('material-requests.index')
            ->with('success', 'Material request ' . $materialRequest->request_number . ' processed.');
    }

    public function reject(Request $request, MaterialRequest $materialRequest)
    {
        Gate::authorize('reject', $materialRequest);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $materialRequest->update([
            'status' => MaterialRequest::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
            'budget_commitment_status' => $materialRequest->budget_commitment_status === MaterialRequest::COMMITMENT_RESERVED
                ? MaterialRequest::COMMITMENT_RELEASED
                : $materialRequest->budget_commitment_status,
        ]);

        $this->audit($materialRequest, 'request_rejected', $materialRequest->quantity, $validated['rejection_reason']);
        if ($materialRequest->wasChanged('budget_commitment_status')) {
            $this->audit($materialRequest, 'budget_commitment_released', null, 'Reserved project budget released.');
        }

        return redirect()
            ->route('material-requests.index')
            ->with('success', 'Material request ' . $materialRequest->request_number . ' rejected.');
    }

    private function issueInventory(MaterialRequest $materialRequest, InventoryItem $item, int $quantity): Transaction
    {
        $category = ExpenseCategory::firstOrCreate(['name' => 'Inventory'], ['name' => 'Inventory']);
        $totalCost = $quantity * (float) $item->unit_cost;

        $transaction = Transaction::create([
            'project_id' => $materialRequest->project_id,
            'type' => 'expense',
            'expense_category_id' => $category->id,
            'expense_name' => '[MATERIAL REQUEST] ' . $item->name . ' x' . $quantity,
            'category' => 'Inventory',
            'amount' => $totalCost,
            'description' => 'Issued through material request ' . $materialRequest->request_number,
            'transaction_date' => now()->toDateString(),
        ]);

        $item->decrement('quantity', $quantity);

        InventoryAssignment::create([
            'inventory_item_id' => $item->id,
            'project_id' => $materialRequest->project_id,
            'transaction_id' => $transaction->id,
            'quantity_assigned' => $quantity,
            'unit_cost_at_assignment' => $item->unit_cost,
            'total_cost' => $totalCost,
            'assigned_by' => Auth::user()->name ?? 'System',
            'notes' => 'Issued from material request ' . $materialRequest->request_number,
        ]);

        return $transaction;
    }

    private function audit(MaterialRequest $materialRequest, string $action, ?int $quantity = null, ?string $notes = null): void
    {
        $user = Auth::user();

        $materialRequest->audits()->create([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'project_id' => $materialRequest->project_id,
            'material_name' => $materialRequest->material_name,
            'quantity' => $quantity,
            'action' => $action,
            'notes' => $notes,
        ]);
    }

    private function nextRequestNumber(): string
    {
        $prefix = 'MR-' . now()->format('Ymd') . '-';
        $count = MaterialRequest::whereDate('created_at', now()->toDateString())->count() + 1;

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function statuses(): array
    {
        return [
            MaterialRequest::STATUS_PENDING,
            MaterialRequest::STATUS_ISSUED,
            MaterialRequest::STATUS_REJECTED,
        ];
    }
}
