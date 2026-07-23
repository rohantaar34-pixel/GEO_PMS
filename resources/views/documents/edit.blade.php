@extends('layouts.app')

@section('content')
    @php
        $documentTypeOptions = \App\Models\Document::documentTypeOptions();
        $categoryOptions = \App\Models\Document::categoryOptions();
        $selectedDocumentType = old('document_type', $document->document_type_option_value);
        $selectedCategory = old('category', $document->category_option_value);
        $customDocumentType = old('document_type_other', $document->document_type_custom_value);
        $customCategory = old('category_other', $document->category_custom_value);
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

        .current-file {
            background: #f3f4f6;
            padding: 8px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 12px;
        }

        .helper-text {
            display: block;
            margin-top: 8px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>

    <div class="form-container">
        <h1 style="font-size: 24px; font-weight: 600; margin-bottom: 24px;">Edit Document: {{ $document->document_number }}
        </h1>

        <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" value="{{ old('title', $document->title) }}" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3">{{ old('description', $document->description) }}</textarea>
            </div>

            <div class="form-group">
                <label>Document Type *</label>
                <select name="document_type" id="document_type" required>
                    @foreach ($documentTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedDocumentType === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="other" @selected($selectedDocumentType === 'other')>Other</option>
                </select>
            </div>

            <div class="form-group" id="document_type_other_group" style="display:none;">
                <label>Specify Document Type *</label>
                <input type="text" name="document_type_other" id="document_type_other" value="{{ $customDocumentType }}"
                    placeholder="Enter document type">
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category" id="category">
                    <option value="">Select Category</option>
                    @foreach ($categoryOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="other" @selected($selectedCategory === 'other')>Other</option>
                </select>
            </div>

            <div class="form-group" id="category_other_group" style="display:none;">
                <label>Specify Category *</label>
                <input type="text" name="category_other" id="category_other" value="{{ $customCategory }}"
                    placeholder="Enter category">
                <small class="helper-text">Use this only when the category is not listed above.</small>
            </div>

            <div class="form-group">
                <label>Associated Project</label>
                <select name="project_id">
                    <option value="">None</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected(old('project_id', $document->project_id) == $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="active" @selected(old('status', $document->status) === 'active')>Active</option>
                    <option value="archived" @selected(old('status', $document->status) === 'archived')>Archived</option>
                    <option value="expired" @selected(old('status', $document->status) === 'expired')>Expired</option>
                </select>
            </div>

            <div class="form-group">
                <label>Document Date</label>
                <input type="date" name="document_date"
                    value="{{ old('document_date', $document->document_date ? $document->document_date->format('Y-m-d') : '') }}">
            </div>

            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date"
                    value="{{ old('expiry_date', $document->expiry_date ? $document->expiry_date->format('Y-m-d') : '') }}">
            </div>

            @if ($document->file_path)
                <div class="form-group">
                    <label>Current Document File</label>
                    <div class="current-file">
                        {{ $document->original_filename }} ({{ $document->formatted_file_size }})
                    </div>
                    <small>Upload a new file to replace it (optional)</small>
                </div>
            @endif

            <div class="form-group">
                <label>Replace Document File (Optional)</label>
                <input type="file" name="document_file" accept=".pdf,.doc,.docx,.xls,.xlsx">
            </div>

            @if ($document->scanned_image_path)
                <div class="form-group">
                    <label>Current Scanned Image</label>
                    <div class="current-file">
                        Current image file is stored
                    </div>
                </div>
            @endif

            <div class="form-group">
                <label>Replace Scanned Image (Optional)</label>
                <input type="file" name="scanned_image" accept="image/*">
            </div>

            <div>
                <button type="submit" class="btn-submit">Update Document</button>
                <a href="{{ route('documents.show', $document) }}" class="btn-cancel">Cancel</a>
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
