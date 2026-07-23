@extends('layouts.app')

@section('content')
    @php
        $documentTypeOptions = \App\Models\Document::documentTypeOptions();
        $categoryOptions = \App\Models\Document::categoryOptions();
    @endphp
    <style>
        .form-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn-submit {
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #374151;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            margin-left: 12px;
        }

        .helper-text {
            display: block;
            margin-top: 8px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>

    <div class="form-container">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
            <a href="{{ route('documents.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f3f4f6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
            <h1 style="font-size: 22px; font-weight: 700; margin:0;">Add New Document</h1>
        </div>

        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label>Document Number</label>
                <input type="text" name="document_number" value="{{ $documentNumber }}" readonly>
                <small style="color: #6b7280;">Auto-generated document ID</small>
            </div>

            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label>Document Type *</label>
                <select name="document_type" id="document_type" required>
                    @foreach ($documentTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('document_type', 'contract') === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="other" @selected(old('document_type') === 'other')>Other</option>
                </select>
            </div>

            <div class="form-group" id="document_type_other_group" style="display:none;">
                <label>Specify Document Type *</label>
                <input type="text" name="document_type_other" id="document_type_other"
                    value="{{ old('document_type_other') }}" placeholder="Enter document type">
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category" id="category">
                    <option value="">Select Category</option>
                    @foreach ($categoryOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="other" @selected(old('category') === 'other')>Other</option>
                </select>
            </div>

            <div class="form-group" id="category_other_group" style="display:none;">
                <label>Specify Category *</label>
                <input type="text" name="category_other" id="category_other" value="{{ old('category_other') }}"
                    placeholder="Enter category">
                <small class="helper-text">Use this only when the category is not listed above.</small>
            </div>

            <div class="form-group">
                <label>Associated Project (Optional)</label>
                <select name="project_id">
                    <option value="">None</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (request('project_id') == $project->id || old('project_id') == $project->id) ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Document Date</label>
                <input type="date" name="document_date" value="{{ old('document_date') }}">
            </div>

            <div class="form-group">
                <label>Expiry Date (If applicable)</label>
                <input type="date" name="expiry_date" value="{{ old('expiry_date') }}">
            </div>

            <div class="form-group">
                <label>Upload Document File (PDF, DOC, etc.)</label>
                <input type="file" name="document_file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                <small>Max size: 10MB</small>
            </div>

            <div class="form-group">
                <label>Upload Scanned Image</label>
                <input type="file" name="scanned_image" accept="image/*">
                <small>Max size: 5MB</small>
            </div>

            <div>
                <button type="submit" class="btn-submit">Save Document</button>
                <a href="{{ route('documents.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleOtherField = (selectId, wrapperId, inputId) => {
                const select = document.getElementById(selectId);
                const wrapper = document.getElementById(wrapperId);
                const input = document.getElementById(inputId);

                if (!select || !wrapper || !input) {
                    return;
                }

                const sync = () => {
                    const show = select.value === 'other';
                    wrapper.style.display = show ? 'block' : 'none';
                    input.required = show;

                    if (!show) {
                        input.value = '';
                    }
                };

                select.addEventListener('change', sync);
                sync();
            };

            toggleOtherField('document_type', 'document_type_other_group', 'document_type_other');
            toggleOtherField('category', 'category_other_group', 'category_other');
        });
    </script>
@endsection
