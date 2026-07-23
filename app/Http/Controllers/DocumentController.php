<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with(['project', 'uploader']);
        
        // Filter by document type
        if ($request->has('type') && $request->type != '') {
            if ($request->type === 'other') {
                $query->whereNotIn('document_type', array_keys(Document::documentTypeOptions()));
            } else {
                $query->where('document_type', $request->type);
            }
        }
        
        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }
        
        // Filter by project
        if ($request->has('project_id') && $request->project_id != '') {
            $query->where('project_id', $request->project_id);
        }
        
        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        
        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $documents = $query->latest('date_added')->paginate(15);
        $projects = Project::all();
        
        // Get statistics
        $stats = [
            'total' => Document::count(),
            'active' => Document::where('status', 'active')->count(),
            'archived' => Document::where('status', 'archived')->count(),
            'recent' => Document::where('date_added', '>=', now()->subDays(30))->count()
        ];
        
        return view('documents.index', compact('documents', 'projects', 'stats'));
    }
    
    public function create()
    {
        $projects = Project::all();
        $documentNumber = $this->generateDocumentNumber();
        
        return view('documents.create', compact('projects', 'documentNumber'));
    }

    public function project(Request $request, Project $project)
    {
        $query = Document::with(['project', 'uploader'])
            ->where('project_id', $project->id);

        if ($request->has('type') && $request->type !== '') {
            if ($request->type === 'other') {
                $query->whereNotIn('document_type', array_keys(Document::documentTypeOptions()));
            } else {
                $query->where('document_type', $request->type);
            }
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest('date_added')->paginate(12)->withQueryString();

        $statsBase = Document::where('project_id', $project->id);
        $stats = [
            'total' => (clone $statsBase)->count(),
            'active' => (clone $statsBase)->where('status', 'active')->count(),
            'with_file' => (clone $statsBase)->whereNotNull('file_path')->count(),
            'with_scan' => (clone $statsBase)->whereNotNull('scanned_image_path')->count(),
        ];

        return view('documents.project', compact('project', 'documents', 'stats'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document_type' => ['required', 'string', Rule::in([...array_keys(Document::documentTypeOptions()), 'other'])],
            'document_type_other' => 'nullable|required_if:document_type,other|string|max:255',
            'category' => ['nullable', 'string', Rule::in([...array_keys(Document::categoryOptions()), 'other'])],
            'category_other' => 'nullable|required_if:category,other|string|max:255',
            'document_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:document_date',
            'project_id' => 'nullable|exists:projects,id',
            'document_file' => 'nullable|file|max:10240', // Max 10MB
            'scanned_image' => 'nullable|image|max:5120', // Max 5MB for images
        ]);
        
        $document = new Document();
        $document->document_number = $request->document_number ?? $this->generateDocumentNumber();
        $document->title = $request->title;
        $document->description = $request->description;
        $document->document_type = $this->resolveSelectableValue(
            $request->document_type,
            $request->document_type_other
        );
        $document->category = $this->resolveSelectableValue(
            $request->category,
            $request->category_other
        );
        $document->document_date = $request->document_date;
        $document->expiry_date = $request->expiry_date;
        $document->project_id = $request->project_id;
        $document->uploaded_by = Auth::id();
        $document->status = 'active';
        $document->date_added = now();
        
        // Handle document file upload
        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $filename = time() . '_' . Str::slug($request->title) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('documents/files', $filename, 'public');
            $document->file_path = $path;
            $document->original_filename = $file->getClientOriginalName();
            $document->file_size = $file->getSize();
            $document->file_extension = $file->getClientOriginalExtension();
            $document->mime_type = $file->getMimeType();
        }
        
        // Handle scanned image upload
        if ($request->hasFile('scanned_image')) {
            $image = $request->file('scanned_image');
            $imageName = time() . '_scan_' . Str::slug($request->title) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('documents/scans', $imageName, 'public');
            $document->scanned_image_path = $imagePath;
        }
        
        $document->save();
        
        return redirect()->route('documents.index')
            ->with('success', 'Document added successfully!');
    }
    
    public function show(Document $document)
    {
        $document->incrementViewCount();
        $document->load(['project', 'uploader']);
        
        return view('documents.show', compact('document'));
    }
    
    public function edit(Document $document)
    {
        $projects = Project::all();
        return view('documents.edit', compact('document', 'projects'));
    }
    
    public function update(Request $request, Document $document)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document_type' => ['required', 'string', Rule::in([...array_keys(Document::documentTypeOptions()), 'other'])],
            'document_type_other' => 'nullable|required_if:document_type,other|string|max:255',
            'category' => ['nullable', 'string', Rule::in([...array_keys(Document::categoryOptions()), 'other'])],
            'category_other' => 'nullable|required_if:category,other|string|max:255',
            'document_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:document_date',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'required|in:active,archived,expired',
            'document_file' => 'nullable|file|max:10240',
            'scanned_image' => 'nullable|image|max:5120',
        ]);
        
        $document->title = $request->title;
        $document->description = $request->description;
        $document->document_type = $this->resolveSelectableValue(
            $request->document_type,
            $request->document_type_other
        );
        $document->category = $this->resolveSelectableValue(
            $request->category,
            $request->category_other
        );
        $document->document_date = $request->document_date;
        $document->expiry_date = $request->expiry_date;
        $document->project_id = $request->project_id;
        $document->status = $request->status;
        
        // Handle new document file upload
        if ($request->hasFile('document_file')) {
            // Delete old file
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            $file = $request->file('document_file');
            $filename = time() . '_' . Str::slug($request->title) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('documents/files', $filename, 'public');
            $document->file_path = $path;
            $document->original_filename = $file->getClientOriginalName();
            $document->file_size = $file->getSize();
            $document->file_extension = $file->getClientOriginalExtension();
            $document->mime_type = $file->getMimeType();
        }
        
        // Handle new scanned image upload
        if ($request->hasFile('scanned_image')) {
            // Delete old image
            if ($document->scanned_image_path && Storage::disk('public')->exists($document->scanned_image_path)) {
                Storage::disk('public')->delete($document->scanned_image_path);
            }
            
            $image = $request->file('scanned_image');
            $imageName = time() . '_scan_' . Str::slug($request->title) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('documents/scans', $imageName, 'public');
            $document->scanned_image_path = $imagePath;
        }
        
        $document->save();
        
        return redirect()->route('documents.show', $document)
            ->with('success', 'Document updated successfully!');
    }
    
    public function destroy(Document $document)
    {
        // Delete files from storage
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        if ($document->scanned_image_path && Storage::disk('public')->exists($document->scanned_image_path)) {
            Storage::disk('public')->delete($document->scanned_image_path);
        }
        
        if ($document->thumbnail_path && Storage::disk('public')->exists($document->thumbnail_path)) {
            Storage::disk('public')->delete($document->thumbnail_path);
        }
        
        $document->delete();
        
        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully!');
    }
    
    public function download(Document $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }
        
        $document->incrementDownloadCount();
        
        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }
    
    public function search(Request $request)
    {
        $search = $request->get('q');
        
        $documents = Document::where('title', 'like', "%{$search}%")
            ->orWhere('document_number', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%")
            ->limit(10)
            ->get();
        
        if ($request->ajax()) {
            return response()->json($documents);
        }
        
        return redirect()->route('documents.index', ['search' => $search]);
    }
    
    public function byCategory($category)
    {
        $documents = Document::where('category', $category)->paginate(15);
        $projects = Project::all();
        $stats = [
            'total' => Document::count(),
            'active' => Document::where('status', 'active')->count(),
            'archived' => Document::where('status', 'archived')->count(),
            'recent' => Document::where('date_added', '>=', now()->subDays(30))->count()
        ];
        
        return view('documents.index', compact('documents', 'projects', 'stats'));
    }
    
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id'
        ]);
        
        $documents = Document::whereIn('id', $request->document_ids)->get();
        
        foreach ($documents as $document) {
            // Delete files
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            if ($document->scanned_image_path && Storage::disk('public')->exists($document->scanned_image_path)) {
                Storage::disk('public')->delete($document->scanned_image_path);
            }
            $document->delete();
        }
        
        return redirect()->route('documents.index')
            ->with('success', count($documents) . ' documents deleted successfully!');
    }
    
    public function bulkMove(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id',
            'project_id' => 'required|exists:projects,id'
        ]);
        
        Document::whereIn('id', $request->document_ids)
            ->update(['project_id' => $request->project_id]);
        
        return redirect()->route('documents.index')
            ->with('success', 'Documents moved successfully!');
    }
    
    public function statistics()
    {
        $stats = [
            'total' => Document::count(),
            'by_type' => Document::selectRaw('document_type, count(*) as count')
                ->groupBy('document_type')
                ->get(),
            'by_category' => Document::selectRaw('category, count(*) as count')
                ->whereNotNull('category')
                ->groupBy('category')
                ->get(),
            'by_status' => Document::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get(),
            'by_month' => Document::selectRaw('DATE_FORMAT(date_added, "%Y-%m") as month, count(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(6)
                ->get(),
        ];
        
        return view('documents.statistics', compact('stats'));
    }
    
    public function exportAll()
    {
        $documents = Document::with(['project', 'uploader'])->get();
        
        // Create CSV export
        $filename = 'documents_export_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w+');
        
        // Add headers
        fputcsv($handle, [
            'Document Number', 'Title', 'Description', 'Type', 'Category', 
            'Status', 'Project', 'Uploaded By', 'Document Date', 'Expiry Date',
            'Date Added', 'View Count', 'Download Count'
        ]);
        
        // Add data
        foreach ($documents as $doc) {
            fputcsv($handle, [
                $doc->document_number,
                $doc->title,
                $doc->description,
                $doc->document_type,
                $doc->category,
                $doc->status,
                $doc->project->name ?? 'N/A',
                $doc->uploader->name ?? 'N/A',
                $doc->document_date,
                $doc->expiry_date,
                $doc->date_added,
                $doc->view_count,
                $doc->download_count,
            ]);
        }
        
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);
        
        return response($csvContent)
            ->withHeaders([
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
    }
    
    public function recent()
    {
        $documents = Document::with(['project', 'uploader'])
            ->latest('date_added')
            ->limit(10)
            ->get();
        
        return response()->json($documents);
    }
    
    public function versionHistory(Document $document)
    {
        // This would require a versions table
        // For now, just return the current document
        return view('documents.versions', compact('document'));
    }
    
    public function restore($id)
    {
        $document = Document::withTrashed()->findOrFail($id);
        $document->restore();
        
        return redirect()->route('documents.index')
            ->with('success', 'Document restored successfully!');
    }
    
    // API Methods for AJAX calls
    public function apiSearch(Request $request)
    {
        $search = $request->get('q');
        $documents = Document::where('title', 'like', "%{$search}%")
            ->orWhere('document_number', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'document_number', 'title', 'document_type']);
        
        return response()->json($documents);
    }
    
    public function apiUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'document_id' => 'nullable|exists:documents,id'
        ]);
        
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('documents/uploads', $filename, 'public');
        
        if ($request->has('document_id')) {
            $document = Document::find($request->document_id);
            $document->file_path = $path;
            $document->save();
        }
        
        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path)
        ]);
    }
    
    public function apiStats()
    {
        $stats = [
            'total' => Document::count(),
            'active' => Document::where('status', 'active')->count(),
            'archived' => Document::where('status', 'archived')->count(),
            'expired' => Document::where('status', 'expired')->count(),
            'recent' => Document::where('date_added', '>=', now()->subDays(7))->count(),
            'by_type' => Document::selectRaw('document_type, count(*) as count')
                ->groupBy('document_type')
                ->get()
        ];
        
        return response()->json($stats);
    }
    
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id',
            'status' => 'required|in:active,archived,expired'
        ]);
        
        Document::whereIn('id', $request->document_ids)
            ->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => count($request->document_ids) . ' documents updated successfully!'
        ]);
    }
    
    private function generateDocumentNumber()
    {
        $prefix = 'DOC';
        $year = date('Y');
        $month = date('m');
        
        $lastDocument = Document::whereYear('date_added', $year)
            ->whereMonth('date_added', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastDocument) {
            $lastNumber = intval(substr($lastDocument->document_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return "{$prefix}-{$year}{$month}-{$newNumber}";
    }

    private function resolveSelectableValue(?string $selectedValue, ?string $customValue): ?string
    {
        $selectedValue = trim((string) $selectedValue);

        if ($selectedValue === '') {
            return null;
        }

        if ($selectedValue !== 'other') {
            return $selectedValue;
        }

        $customValue = Str::of((string) $customValue)->squish()->toString();

        return $customValue === '' ? 'other' : $customValue;
    }
}
