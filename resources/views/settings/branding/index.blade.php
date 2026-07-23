@extends('layouts.app')

@section('content')
    <style>
        .settings-container {
            padding: 24px;
            background: #f8f8fb;
            min-height: calc(100vh - 80px);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .settings-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .settings-tab {
            display: inline-flex;
            align-items: center;
            padding: 9px 14px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #374151;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .settings-tab.active {
            background: var(--brand-primary);
            color: #fff;
            border-color: var(--brand-primary);
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            margin: 0;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-top: 6px;
            max-width: 700px;
        }

        .brand-grid {
            display: grid;
            grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
            gap: 20px;
        }

        .panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .panel-body {
            padding: 24px;
        }

        .panel-title {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 16px;
        }

        .brand-preview {
            position: relative;
            min-height: 240px;
            border-radius: 18px;
            padding: 24px;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(135deg, {{ $settings->resolved_primary_color }} 0%, {{ $settings->primary_color_dark }} 100%);
        }

        .brand-preview::after {
            content: '';
            position: absolute;
            inset: auto -40px -60px auto;
            width: 180px;
            height: 180px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
        }

        .brand-preview-logo {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            backdrop-filter: blur(6px);
            margin-bottom: 18px;
        }

        .brand-preview-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .brand-preview-name {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }

        .brand-preview-tagline {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 18px;
        }

        .brand-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 12px;
            font-weight: 700;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
        }

        .form-group input[type="text"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            background: #fff;
        }

        .color-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .color-row input[type="color"] {
            width: 64px;
            height: 44px;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .color-row input[type="text"] {
            flex: 1;
        }

        .current-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fafafa;
            margin-bottom: 12px;
        }

        .current-logo img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #e5e7eb;
            padding: 6px;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-top: 10px;
        }

        .checkbox-row input {
            width: 18px;
            height: 18px;
            accent-color: var(--brand-primary);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 28px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
            color: white;
            padding: 12px 22px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            box-shadow: 0 12px 28px rgba(var(--brand-primary-rgb), 0.22);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        @media (max-width: 920px) {
            .brand-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .settings-container {
                padding: 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="settings-container">
        <div class="settings-tabs">
            <a href="{{ route('settings.projects.index') }}" class="settings-tab">Projects</a>
            <a href="{{ route('settings.users.index') }}" class="settings-tab">Users</a>
            <a href="{{ route('settings.branding.index') }}" class="settings-tab active">Branding</a>
        </div>

        <div class="top-row">
            <div>
                <h1 class="page-title">Branding CMS</h1>
                <div class="page-subtitle">Update the system name, logo, and primary brand color shown across the
                    application. This page is limited to the super admin account.</div>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Back to Dashboard</a>
        </div>

        <div class="brand-grid">
            <div class="panel">
                <div class="panel-body">
                    <h2 class="panel-title">Live Preview</h2>
                    <div class="brand-preview">
                        <div class="brand-preview-logo">
                            <img src="{{ $settings->logo_url }}" alt="{{ $settings->resolved_name }} logo">
                        </div>
                        <div class="brand-preview-name">{{ $settings->resolved_short_name }}</div>
                        <div class="brand-preview-tagline">{{ $settings->resolved_tagline }}</div>
                        <div class="brand-chip">{{ $settings->resolved_name }}</div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-body">
                    <h2 class="panel-title">Brand Settings</h2>
                    <form action="{{ route('settings.branding.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="system_name">System Name *</label>
                                <input type="text" id="system_name" name="system_name"
                                    value="{{ old('system_name', $settings->resolved_name) }}" required>
                            </div>

                            <div class="form-group">
                                <label for="system_short_name">Short Name</label>
                                <input type="text" id="system_short_name" name="system_short_name"
                                    value="{{ old('system_short_name', $settings->system_short_name) }}"
                                    placeholder="ARDC">
                            </div>

                            <div class="form-group full">
                                <label for="system_tagline">Tagline</label>
                                <input type="text" id="system_tagline" name="system_tagline"
                                    value="{{ old('system_tagline', $settings->system_tagline) }}"
                                    placeholder="Project Management">
                            </div>

                            <div class="form-group full">
                                <label for="primary_color">Primary Color *</label>
                                <div class="color-row">
                                    <input type="color" id="primary_color_picker"
                                        value="{{ old('primary_color', $settings->resolved_primary_color) }}">
                                    <input type="text" id="primary_color" name="primary_color"
                                        value="{{ old('primary_color', $settings->resolved_primary_color) }}"
                                        pattern="^#[0-9A-Fa-f]{6}$" required>
                                </div>
                                <div class="muted" style="margin-top:8px;">Used on the top bar, login screen, buttons, and
                                    main brand highlights.</div>
                            </div>

                            <div class="form-group full">
                                <label for="logo">Logo</label>
                                <div class="current-logo">
                                    <img src="{{ $settings->logo_url }}" alt="{{ $settings->resolved_name }} logo">
                                    <div>
                                        <div style="font-weight:800; color:#111827;">Current Logo</div>
                                        <div class="muted">
                                            {{ $settings->logo_path ? basename($settings->logo_path) : 'Default bundled logo' }}
                                        </div>
                                    </div>
                                </div>
                                <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg">
                                <div class="muted" style="margin-top:8px;">Accepted formats: JPG, PNG, WEBP, SVG. Max size:
                                    2 MB.</div>
                                @if ($settings->logo_path)
                                    <label class="checkbox-row">
                                        <input type="checkbox" name="remove_logo" value="1">
                                        Remove uploaded logo and revert to the default logo
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Save Branding</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const colorInput = document.getElementById('primary_color');
        const colorPicker = document.getElementById('primary_color_picker');

        if (colorInput && colorPicker) {
            colorPicker.addEventListener('input', () => {
                colorInput.value = colorPicker.value.toUpperCase();
            });

            colorInput.addEventListener('input', () => {
                if (/^#[0-9A-Fa-f]{6}$/.test(colorInput.value)) {
                    colorPicker.value = colorInput.value;
                }
            });
        }
    </script>
@endsection
