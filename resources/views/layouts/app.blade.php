{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="true">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>{{ $systemSettings->resolved_name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            font-family: 'Montserrat', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            letter-spacing: -0.015em;
        }

        :root {
            --brand-primary: {{ $systemSettings->resolved_primary_color }};
            --brand-primary-dark: {{ $systemSettings->primary_color_dark }};
            --brand-primary-light: {{ $systemSettings->primary_color_light }};
            --brand-primary-rgb: {{ $systemSettings->primary_color_rgb }};
            --brand-accent: #0F9F8F;
            --brand-accent-dark: #0F766E;
            --brand-accent-soft: #ECFDF8;
            --brand-blue-soft: #EFF6FF;
            --surface-border: #E2E8F0;
            --surface-border-strong: #DCE3EC;
            --text-muted: #64748B;
        }

        body {
            background: linear-gradient(135deg, #F8F9FC 0%, #F3F4F8 100%);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        html,
        body {
            touch-action: manipulation;
        }

        input,
        textarea,
        select {
            -webkit-appearance: none;
            appearance: none;
            font-size: 16px;
            font-family: 'Montserrat', sans-serif;
        }

        button {
            -webkit-appearance: none;
            appearance: none;
            font-family: 'Montserrat', sans-serif;
        }

        .nav-logout-form {
            margin: 0;
        }

        .btn-nav-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 7px 16px;
            border-radius: 11px;
            border: 1px solid transparent;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
        }

        .btn-nav-action.settings {
            background: var(--brand-blue-soft);
            border-color: #BFDBFE;
            color: var(--brand-primary);
        }

        .btn-nav-action.settings:hover {
            background: #DBEAFE;
        }

        .btn-nav-action.logout {
            background: var(--brand-primary-dark);
            border-color: var(--brand-primary-dark);
            color: #FFF;
        }

        .btn-nav-action.logout:hover {
            background: var(--brand-primary);
            border-color: var(--brand-primary);
        }

        .nav-actions {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 10px;
        }

        .nav-user-chip {
            display: inline-flex;
            min-width: 0;
            align-items: center;
            gap: 8px;
            padding: 5px 9px 5px 6px;
            border: 1px solid var(--surface-border);
            border-radius: 10px;
            background: #F8FAFC;
            color: #475569;
        }

        .nav-user-avatar {
            display: grid;
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 8px;
            background: #D9F6F1;
            color: var(--brand-accent-dark);
            font-size: 11px;
            font-weight: 900;
        }

        .nav-user-name {
            max-width: 170px;
            overflow: hidden;
            font-size: .72rem;
            font-weight: 750;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .notification-nav {
            position: relative;
            flex: 0 0 auto;
        }

        .notification-bell {
            position: relative;
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border: 1px solid var(--surface-border);
            border-radius: 11px;
            background: #FFF;
            color: #475569;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            display: grid;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            place-items: center;
            border: 2px solid #FFF;
            border-radius: 999px;
            background: var(--brand-accent);
            color: #FFF;
            font-size: 9px;
            font-weight: 900;
            line-height: 1;
        }

        .notification-badge[hidden] {
            display: none;
        }

        .app-confirm-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            inset: 0;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, .62);
            backdrop-filter: blur(3px);
        }

        .app-confirm-overlay.open {
            display: flex;
        }

        .app-confirm-card {
            width: min(440px, 100%);
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: 18px;
            background: #FFF;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .3);
            animation: app-confirm-in .18s ease-out;
        }

        .app-confirm-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 20px 13px;
        }

        .app-confirm-icon {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 11px;
            background: #FEE2E2;
            color: #DC2626;
            font-size: 20px;
            font-weight: 900;
        }

        .app-confirm-title-wrap {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 12px;
        }

        .app-confirm-title {
            margin: 0;
            color: #111827;
            font-size: 17px;
            line-height: 1.3;
        }

        .app-confirm-close {
            display: grid;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            place-items: center;
            border: 0;
            border-radius: 9px;
            background: #F1F5F9;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
        }

        .app-confirm-message {
            margin: 0;
            padding: 0 20px 20px;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.65;
            overflow-wrap: anywhere;
        }

        .app-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid var(--surface-border);
            background: #F8FAFC;
        }

        .app-confirm-btn {
            min-height: 40px;
            padding: 9px 15px;
            border: 0;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 850;
            cursor: pointer;
        }

        .app-confirm-cancel {
            background: #E2E8F0;
            color: #334155;
        }

        .app-confirm-submit {
            background: var(--brand-primary);
            color: #FFF;
        }

        @keyframes app-confirm-in {
            from {
                opacity: 0;
                transform: translateY(8px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }

        @media (max-width: 640px) {
            body {
                overflow-x: hidden;
            }

            .nav-actions {
                gap: 6px;
            }

            .nav-user-chip {
                padding: 4px;
                border: 0;
                background: transparent;
            }

            .nav-user-name {
                display: none;
            }

            .btn-nav-action {
                padding: 7px 10px;
                font-size: .67rem;
            }

            .notification-bell {
                width: 36px;
                height: 36px;
            }

            .app-confirm-overlay {
                align-items: flex-end;
                padding: 12px;
            }

            .app-confirm-card {
                border-radius: 18px;
            }

            .app-confirm-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .app-confirm-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    @php
        $currentUser = Auth::user();
        $homeRoute = $currentUser
            ? ($currentUser->isEmployee() ? route('monitoring.submit') : ($currentUser->isOfficeEngineer() ? route('monitoring.index') : route('dashboard')))
            : route('login');
        $settingsRoute = $currentUser?->isAdmin() ? route('settings.projects.index') : null;
        $userInitial = $currentUser ? strtoupper(substr(trim($currentUser->name), 0, 1)) : 'U';
    @endphp

    <div class="min-h-screen flex flex-col">
        <nav class="bg-white sticky top-0 z-40 shadow-sm" style="border-bottom: 4px solid var(--brand-accent);">
            <div class="w-full px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
                <a href="{{ $homeRoute }}"
                    class="flex items-center gap-2 sm:gap-3 flex-shrink-0 hover:opacity-80 transition-opacity">
                    <img src="{{ $systemSettings->logo_url }}" alt="{{ $systemSettings->resolved_short_name }} Logo"
                        class="h-10 sm:h-12 w-auto object-contain">
                    <div class="hidden sm:flex flex-col">
                        <span class="text-sm font-black text-slate-900">{{ $systemSettings->resolved_short_name }}</span>
                        <p class="text-xs font-bold leading-tight"
                            style="color: var(--brand-accent-dark);">{{ $systemSettings->resolved_tagline }}</p>
                    </div>
                </a>

                @auth
                    <div class="nav-actions">
                        <div class="notification-nav" aria-hidden="true">
                            <div class="notification-bell">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span class="notification-badge" hidden>0</span>
                            </div>
                        </div>

                        <div class="nav-user-chip">
                            <span class="nav-user-avatar">{{ $userInitial }}</span>
                            <span class="nav-user-name">{{ $currentUser->name }}</span>
                        </div>

                        @if ($settingsRoute)
                            <a href="{{ $settingsRoute }}" class="btn-nav-action settings">
                                Settings
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="nav-logout-form">
                            @csrf
                            <button type="submit" class="btn-nav-action logout">
                                Logout
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </nav>

        <div class="flex-1 w-full px-4 sm:px-6 py-6 sm:py-12">
            <div class="max-w-7xl mx-auto">
                @if (session('success'))
                    <div
                        class="mb-6 sm:mb-8 p-4 px-4 sm:px-6 bg-gradient-to-r from-green-50 to-emerald-100 border-2 border-green-600 text-green-700 rounded-xl flex items-center gap-3 animate-fade-in">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="font-bold text-sm sm:text-base">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        class="mb-6 sm:mb-8 p-4 px-4 sm:px-6 bg-gradient-to-r from-red-50 to-red-100 border-2 border-red-600 text-red-700 rounded-xl flex items-center gap-3 animate-fade-in">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="font-bold text-sm sm:text-base">{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mb-6 sm:mb-8 p-4 px-4 sm:px-6 bg-gradient-to-r from-red-50 to-red-100 border-2 border-red-600 text-red-700 rounded-xl animate-fade-in">
                        <h4 class="font-bold mb-2 text-sm sm:text-base">Validation Errors:</h4>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li class="text-sm">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <div class="app-confirm-overlay" id="appConfirmModal" aria-hidden="true">
        <div class="app-confirm-card" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle"
            aria-describedby="appConfirmMessage">
            <div class="app-confirm-head">
                <div class="app-confirm-title-wrap">
                    <span class="app-confirm-icon" aria-hidden="true">!</span>
                    <h2 class="app-confirm-title" id="appConfirmTitle">Confirm action</h2>
                </div>
                <button type="button" class="app-confirm-close" id="appConfirmClose"
                    aria-label="Close confirmation">&times;</button>
            </div>
            <p class="app-confirm-message" id="appConfirmMessage">Are you sure you want to continue?</p>
            <div class="app-confirm-actions">
                <button type="button" class="app-confirm-btn app-confirm-cancel" id="appConfirmCancel">Cancel</button>
                <button type="button" class="app-confirm-btn app-confirm-submit" id="appConfirmSubmit">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('appConfirmModal');
            const title = document.getElementById('appConfirmTitle');
            const message = document.getElementById('appConfirmMessage');
            const confirmButton = document.getElementById('appConfirmSubmit');
            const cancelButton = document.getElementById('appConfirmCancel');
            const closeButton = document.getElementById('appConfirmClose');
            let pendingForm = null;
            let pendingSubmitter = null;

            function closeConfirmation() {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
                pendingSubmitter = null;
                document.body.style.overflow = '';
            }

            function openConfirmation(form, submitter) {
                pendingForm = form;
                pendingSubmitter = submitter || null;
                title.textContent = form.dataset.confirmTitle || 'Confirm action';
                message.textContent = form.dataset.confirmMessage || 'Are you sure you want to continue?';
                confirmButton.textContent = form.dataset.confirmButton || form.dataset.confirmAction || 'Confirm';
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                confirmButton.focus();
            }

            document.addEventListener('submit', event => {
                const form = event.target.closest('form[data-confirm], form[data-confirm-message]');

                if (!form || form.dataset.confirmApproved === 'true') {
                    if (form) delete form.dataset.confirmApproved;
                    return;
                }

                event.preventDefault();
                openConfirmation(form, event.submitter);
            });

            confirmButton.addEventListener('click', () => {
                if (!pendingForm) {
                    return;
                }

                const form = pendingForm;
                const submitter = pendingSubmitter;
                form.dataset.confirmApproved = 'true';
                closeConfirmation();

                if (typeof form.requestSubmit === 'function') {
                    submitter ? form.requestSubmit(submitter) : form.requestSubmit();
                } else {
                    form.submit();
                }
            });

            cancelButton.addEventListener('click', closeConfirmation);
            closeButton.addEventListener('click', closeConfirmation);
            modal.addEventListener('click', event => {
                if (event.target === modal) {
                    closeConfirmation();
                }
            });
            window.addEventListener('keydown', event => {
                if (event.key === 'Escape' && modal.classList.contains('open')) {
                    closeConfirmation();
                }
            });
        })();
    </script>
</body>

</html>
