@extends('layouts.app')

@section('content')
<style>
    :root {
        --indigo: #6366f1;
        --indigo-dark: #4f46e5;
        --indigo-light: #eef2ff;
        --green: #059669;
        --green-light: #ecfdf5;
        --red: #dc2626;
        --red-light: #fef2f2;
        --orange: #ea580c;
        --ink: #111827;
        --ink-2: #374151;
        --ink-3: #6b7280;
        --border: #e8e8ed;
        --bg: #f8f8fb;
        --radius: 16px;
        --radius-sm: 10px;
    }

    * { box-sizing: border-box; }

    .project-docs-page {
        padding: 24px 28px 40px;
        background: var(--bg);
        min-height: calc(100vh - 80px);
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .crumbs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .btn-nav {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: white;
        color: var(--ink-2);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
    }

    .btn-nav:hover {
        background: var(--indigo-light);
        color: var(--indigo-dark);
        border-color: var(--indigo);
    }

    .page-title {
        font-size: 28px;
        font-weight: 800;
        color: var(--ink);
        margin: 0 0 6px;
    }

    .page-subtitle {
        color: var(--ink-3);
        font-size: 14px;
        max-width: 780px;
        line-height: 1.5;
    }

    .meta-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        background: white;
        border: 1px solid var(--border);
        color: var(--ink-2);
        font-size: 12px;
        font-weight: 700;
    }

    .header-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        background: var(--indigo-dark);
        color: white;
        text-decoration: none;
        border-radius: var(--radius-sm);
        font-size: 14px;
        font-weight: 800;
    }

    .btn-primary:hover { background: #4338ca; }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }

    .stat-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 18px 20px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--indigo-dark);
    }

    .stat-label {
        margin-top: 6px;
        color: var(--ink-3);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .filter-card {
        background: white;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 16px 18px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 24px;
    }

    .filter-card input,
    .filter-card select {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        background: white;
    }

    .filter-card input {
        flex: 1;
        min-width: 220px;
    }

    .filter-card input:focus,
    .filter-card select:focus {
        border-color: var(--indigo);
    }

    .btn-filter {
        background: var(--ink-2);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-reset {
        color: var(--ink-2);
        background: #f9fafb;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }

    .doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }

    .doc-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
    }

    .doc-card-head {
        padding: 18px 18px 12px;
        border-bottom: 1px solid #f3f4f6;
    }

    .doc-head-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .doc-title {
        font-size: 17px;
        font-weight: 800;
        color: var(--ink);
        margin: 0;
    }

    .doc-number {
        color: var(--ink-3);
        font-size: 12px;
        font-weight: 700;
        margin-top: 4px;
    }

    .doc-status {
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .doc-status.active { background: var(--green-light); color: var(--green); }
    .doc-status.archived { background: #f3f4f6; color: var(--ink-2); }
    .doc-status.expired { background: var(--red-light); color: var(--red); }

    .doc-tags {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .doc-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #f8fafc;
        color: var(--ink-2);
    }

    .doc-type-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .type-contract { background: #6366f1; }
    .type-invoice { background: #f59e0b; }
    .type-report { background: #10b981; }
    .type-other { background: #9ca3af; }

    .doc-card-body {
        padding: 16px 18px 18px;
    }

    .doc-description {
        color: var(--ink-2);
        font-size: 13px;
        line-height: 1.6;
        min-height: 42px;
        margin-bottom: 14px;
    }

    .doc-meta-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 14px;
        margin-bottom: 16px;
    }

    .meta-label {
        color: var(--ink-3);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .meta-value {
        color: var(--ink);
        font-size: 13px;
        font-weight: 600;
    }

    .asset-list {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .asset-pill {
        background: var(--indigo-light);
        color: var(--indigo-dark);
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 800;
    }

    .doc-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .doc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 12px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 800;
    }

    .doc-btn.view { background: #e0e7ff; color: #4338ca; }
    .doc-btn.edit { background: var(--green-light); color: var(--green); }
    .doc-btn.file { background: #fef3c7; color: #92400e; }
    .doc-btn.scan { background: #fce7f3; color: #be185d; }

    .empty-state {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 48px 24px;
        text-align: center;
        color: var(--ink-3);
    }

    .empty-title {
        font-size: 18px;
        font-weight: 800;
        color: var(--ink);
        margin-bottom: 8px;
    }

    .pagination-wrap {
        margin-top: 22px;
    }

    @media (max-width: 768px) {
        .project-docs-page {
            padding: 18px 16px 32px;
        }

        .doc-meta-list {
            grid-template-columns: 1fr;
        }

        .page-title {
            font-size: 24px;
        }
    }
</style>

<div class="project-docs-page">
    <div class="crumbs">
        <a href="{{ route('documents.index') }}" class="btn-nav">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Document Tracker
        </a>
        <a href="{{ route('dashboard') }}" class="btn-nav">Dashboard</a>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $project->name }}</h1>
            <div class="page-subtitle">
                {{ $project->description ?: 'View all documents, files, and scanned copies associated with this project.' }}
            </div>
            <div class="meta-row">
                <div class="meta-pill">Status: {{ $project->status_label }}</div>
                <div class="meta-pill">Project Documents: {{ $stats['total'] }}</div>
            </div>
        </div>

        <div class="header-actions">
            <a href="{{ route('documents.create', ['project_id' => $project->id]) }}" class="btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Document
            </a>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Total Documents</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--green);">{{ $stats['active'] }}</div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--orange);">{{ $stats['with_file'] }}</div>
            <div class="stat-label">With Files</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #db2777;">{{ $stats['with_scan'] }}</div>
            <div class="stat-label">With Scanned Copies</div>
        </div>
    </div>

    <form method="GET" class="filter-card">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search within this project's documents...">
        <select name="type">
            <option value="">All Types</option>
            <option value="contract" @selected(request('type') === 'contract')>Contract</option>
            <option value="invoice" @selected(request('type') === 'invoice')>Invoice</option>
            <option value="report" @selected(request('type') === 'report')>Report</option>
            <option value="other" @selected(request('type') === 'other')>Other / Custom</option>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            <option value="expired" @selected(request('status') === 'expired')>Expired</option>
        </select>
        <button type="submit" class="btn-filter">Filter</button>
        <a href="{{ route('documents.projects.show', $project) }}" class="btn-reset">Reset</a>
    </form>

    @if ($documents->count() > 0)
        <div class="doc-grid">
            @foreach ($documents as $document)
                <div class="doc-card">
                    <div class="doc-card-head">
                        <div class="doc-head-top">
                            <div>
                                <h2 class="doc-title">{{ $document->title }}</h2>
                                <div class="doc-number">{{ $document->document_number }}</div>
                            </div>
                            <div class="doc-status {{ $document->status }}">{{ ucfirst($document->status) }}</div>
                        </div>

                        <div class="doc-tags">
                            <div class="doc-tag">
                                <span class="doc-type-dot type-{{ $document->document_type_css_class }}"></span>
                                {{ $document->document_type_display }}
                            </div>
                            <div class="doc-tag">{{ $document->category_display }}</div>
                        </div>
                    </div>

                    <div class="doc-card-body">
                        <div class="doc-description">
                            {{ $document->description ?: 'No description provided for this document.' }}
                        </div>

                        <div class="doc-meta-list">
                            <div>
                                <div class="meta-label">Document Date</div>
                                <div class="meta-value">{{ $document->document_date ? $document->document_date->format('M d, Y') : 'Not set' }}</div>
                            </div>
                            <div>
                                <div class="meta-label">Uploaded</div>
                                <div class="meta-value">{{ $document->date_added ? $document->date_added->format('M d, Y') : 'Unknown' }}</div>
                            </div>
                            <div>
                                <div class="meta-label">Uploaded By</div>
                                <div class="meta-value">{{ $document->uploader?->name ?? 'System' }}</div>
                            </div>
                            <div>
                                <div class="meta-label">Expiry</div>
                                <div class="meta-value">{{ $document->expiry_date ? $document->expiry_date->format('M d, Y') : 'No expiry' }}</div>
                            </div>
                        </div>

                        <div class="asset-list">
                            @if ($document->file_path)
                                <div class="asset-pill">Document File</div>
                            @endif
                            @if ($document->scanned_image_path)
                                <div class="asset-pill">Scanned Copy</div>
                            @endif
                            @if (! $document->file_path && ! $document->scanned_image_path)
                                <div class="asset-pill" style="background:#f3f4f6;color:#4b5563;">No Attachments</div>
                            @endif
                        </div>

                        <div class="doc-actions">
                            <a href="{{ route('documents.show', $document) }}" class="doc-btn view">View Details</a>
                            <a href="{{ route('documents.edit', $document) }}" class="doc-btn edit">Edit</a>
                            @if ($document->file_path)
                                <a href="{{ route('documents.download', $document) }}" class="doc-btn file">Download File</a>
                            @endif
                            @if ($document->scanned_image_path)
                                <a href="{{ $document->scanned_image_url }}" target="_blank" class="doc-btn scan">View Scan</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pagination-wrap">
            {{ $documents->links() }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-title">No documents found for {{ $project->name }}</div>
            <div>Try changing the filters or add the first document for this project.</div>
        </div>
    @endif
</div>
@endsection
