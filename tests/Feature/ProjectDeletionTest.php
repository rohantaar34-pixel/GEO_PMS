<?php

use App\Models\Document;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\ProjectMonitoringPhoto;
use App\Models\ProjectMonitoringReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('deleting a project removes associated records and stored files', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $employee = User::factory()->create(['role' => 'employee']);

    $project = Project::create([
        'name' => 'Bacolod Project',
        'description' => 'Project slated for deletion',
        'budget' => 500000,
        'status' => 'not_started',
    ]);

    $employee->assignedProjects()->attach($project->id);

    $documentFile = 'documents/files/bacolod-initial.pdf';
    $scannedImage = 'documents/scans/bacolod-initial.png';
    $thumbnail = 'documents/thumbnails/bacolod-initial.jpg';
    $proofImage = 'transactions/proofs/bacolod-proof.jpg';
    $monitoringPhoto = 'monitoring_photos/bacolod-progress.jpg';

    Storage::disk('public')->put($documentFile, 'document');
    Storage::disk('public')->put($scannedImage, 'scan');
    Storage::disk('public')->put($thumbnail, 'thumb');
    Storage::disk('public')->put($proofImage, 'proof');
    Storage::disk('public')->put($monitoringPhoto, 'photo');

    $document = Document::create([
        'document_number' => 'DOC-202607-0001',
        'title' => 'Initial Document',
        'description' => 'Document linked to project deletion',
        'document_type' => 'report',
        'category' => 'technical',
        'file_path' => $documentFile,
        'scanned_image_path' => $scannedImage,
        'thumbnail_path' => $thumbnail,
        'project_id' => $project->id,
        'uploaded_by' => $admin->id,
        'status' => 'active',
        'date_added' => now(),
    ]);

    $transaction = Transaction::create([
        'project_id' => $project->id,
        'type' => 'expense',
        'expense_name' => 'Initial Procurement',
        'proof_image' => $proofImage,
        'category' => 'materials',
        'amount' => 1500,
        'description' => 'Expense slated for cascade delete',
        'transaction_date' => now()->toDateString(),
    ]);

    $inventoryItem = InventoryItem::create([
        'name' => 'Cement',
        'unit' => 'bags',
        'unit_cost' => 250,
        'quantity' => 50,
    ]);

    $inventoryAssignment = InventoryAssignment::create([
        'inventory_item_id' => $inventoryItem->id,
        'project_id' => $project->id,
        'transaction_id' => $transaction->id,
        'quantity_assigned' => 3,
        'unit_cost_at_assignment' => 250,
        'total_cost' => 750,
        'assigned_by' => 'admin',
    ]);

    $materialRequest = MaterialRequest::create([
        'request_number' => 'MR-20260722-0001',
        'project_id' => $project->id,
        'user_id' => $employee->id,
        'material_name' => 'Steel Bars',
        'material_category' => 'Construction Materials',
        'quantity' => 10,
        'unit' => 'pcs',
        'purpose' => 'Foundation support',
        'request_note' => 'Linked to deleted project',
        'date_requested' => now()->toDateString(),
        'status' => MaterialRequest::STATUS_PENDING,
    ]);

    $report = ProjectMonitoringReport::create([
        'project_id' => $project->id,
        'user_id' => $employee->id,
        'accomplishment_details' => 'Initial site prep complete',
        'estimated_completion_percentage' => 15,
        'status' => ProjectMonitoringReport::STATUS_PENDING,
    ]);

    $photo = ProjectMonitoringPhoto::create([
        'project_monitoring_report_id' => $report->id,
        'path' => $monitoringPhoto,
        'original_name' => 'progress.jpg',
    ]);

    Storage::disk('public')->assertExists($documentFile);
    Storage::disk('public')->assertExists($scannedImage);
    Storage::disk('public')->assertExists($thumbnail);
    Storage::disk('public')->assertExists($proofImage);
    Storage::disk('public')->assertExists($monitoringPhoto);

    $response = $this->actingAs($admin)
        ->delete(route('settings.projects.destroy', $project));

    $response->assertRedirect(route('settings.projects.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    $this->assertDatabaseMissing('inventory_assignments', ['id' => $inventoryAssignment->id]);
    $this->assertDatabaseMissing('material_requests', ['id' => $materialRequest->id]);
    $this->assertDatabaseMissing('project_monitoring_reports', ['id' => $report->id]);
    $this->assertDatabaseMissing('project_monitoring_photos', ['id' => $photo->id]);
    $this->assertDatabaseMissing('project_user', [
        'project_id' => $project->id,
        'user_id' => $employee->id,
    ]);

    Storage::disk('public')->assertMissing($documentFile);
    Storage::disk('public')->assertMissing($scannedImage);
    Storage::disk('public')->assertMissing($thumbnail);
    Storage::disk('public')->assertMissing($proofImage);
    Storage::disk('public')->assertMissing($monitoringPhoto);
});
