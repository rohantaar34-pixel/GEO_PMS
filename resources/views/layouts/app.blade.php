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
        }

        body {
            background: linear-gradient(135deg, #f8f9fc 0%, #f3f4f8 100%);
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

        /* Logout button in nav */
        .nav-logout-form {
            margin: 0;
        }

        .btn-nav-logout {
            padding: 7px 16px;
            background: rgba(255, 255, 255, .15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 7px;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            transition: background .15s;
        }

        .btn-nav-logout:hover {
            background: rgba(255, 255, 255, .25);
        }

        .confirm-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.58);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .confirm-modal.open {
            opacity: 1;
            pointer-events: auto;
        }

        .confirm-modal-card {
            width: min(460px, 100%);
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
            padding: 24px;
            transform: translateY(18px) scale(0.98);
            transition: transform 0.2s ease;
        }

        .confirm-modal.open .confirm-modal-card {
            transform: translateY(0) scale(1);
        }

        .confirm-modal-badge {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #dc2626;
            margin-bottom: 18px;
        }

        .confirm-modal-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 10px;
            letter-spacing: -0.02em;
        }

        .confirm-modal-message {
            margin: 0;
            color: #475569;
            font-size: 15px;
            line-height: 1.65;
        }

        .confirm-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .confirm-modal-btn {
            min-width: 120px;
            padding: 12px 18px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .confirm-modal-btn:hover {
            transform: translateY(-1px);
        }

        .confirm-modal-btn.cancel {
            background: #fff;
            border-color: #cbd5e1;
            color: #334155;
        }

        .confirm-modal-btn.confirm {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            box-shadow: 0 12px 28px rgba(220, 38, 38, 0.22);
        }

        .confirm-modal-btn.confirm[data-variant="primary"] {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            box-shadow: 0 12px 28px rgba(79, 70, 229, 0.22);
        }
    </style>
</head>

<body>
    <div class="min-h-screen flex flex-col">

        <!-- Navigation Bar -->
        <nav class="bg-white border-b-4 sticky top-0 z-40 shadow-sm" style="border-color: var(--brand-primary);">
            <div class="w-full px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">

                <a href="{{ Auth::check() && Auth::user()->isEmployee() ? route('monitoring.submit') : (Auth::check() && Auth::user()->isOfficeEngineer() ? route('monitoring.index') : route('dashboard')) }}"
                    class="flex items-center gap-2 sm:gap-3 flex-shrink-0 hover:opacity-80 transition-opacity">
                    <img src="{{ $systemSettings->logo_url }}" alt="{{ $systemSettings->resolved_name }} Logo"
                        class="h-10 sm:h-12 w-auto object-contain">
                    <div class="hidden sm:flex flex-col">
                        <span class="text-sm font-black text-slate-900">{{ $systemSettings->resolved_short_name }}</span>
                        <p class="text-xs font-bold leading-tight" style="color: var(--brand-primary);">{{ $systemSettings->resolved_tagline }}</p>
                    </div>
                </a>

                @auth
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:.8rem; color:#888; font-weight:600;">{{ Auth::user()->name }}</span>
                        
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('settings.projects.index') }}" class="btn-nav-logout" style="background: rgba(var(--brand-primary-rgb), 0.12); border-color: rgba(var(--brand-primary-rgb), 0.26); color: var(--brand-primary); text-decoration: none;">
                                Settings
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="nav-logout-form">
                            @csrf
                            <button type="submit" class="btn-nav-logout" style="background: var(--brand-primary); border-color: var(--brand-primary);">
                                Logout
                            </button>
                        </form>
                    </div>
                @endauth

            </div>
        </nav>

        <!-- Main Content -->
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

    <div id="confirmModal" class="confirm-modal" aria-hidden="true">
        <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
            <div class="confirm-modal-badge" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                </svg>
            </div>
            <h2 id="confirmModalTitle" class="confirm-modal-title">Confirm action</h2>
            <p id="confirmModalMessage" class="confirm-modal-message">Please confirm this action.</p>
            <div class="confirm-modal-actions">
                <button type="button" id="confirmModalCancel" class="confirm-modal-btn cancel">Cancel</button>
                <button type="button" id="confirmModalConfirm" class="confirm-modal-btn confirm" data-variant="danger">Confirm</button>
            </div>
        </div>
    </div>

    <style>
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

            .confirm-modal-card {
                padding: 22px 18px 18px;
                border-radius: 18px;
            }

            .confirm-modal-title {
                font-size: 20px;
            }

            .confirm-modal-actions {
                flex-direction: column-reverse;
            }

            .confirm-modal-btn {
                width: 100%;
            }
        }
    </style>
    <script>
        (() => {
            const modal = document.getElementById('confirmModal');
            const title = document.getElementById('confirmModalTitle');
            const message = document.getElementById('confirmModalMessage');
            const cancelButton = document.getElementById('confirmModalCancel');
            const confirmButton = document.getElementById('confirmModalConfirm');

            if (!modal || !title || !message || !cancelButton || !confirmButton) {
                return;
            }

            let pendingForm = null;
            let lastActiveElement = null;

            const closeModal = () => {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';

                if (lastActiveElement instanceof HTMLElement) {
                    lastActiveElement.focus();
                }

                pendingForm = null;
                lastActiveElement = null;
            };

            const openModal = (form) => {
                pendingForm = form;
                lastActiveElement = document.activeElement;
                title.textContent = form.dataset.confirmTitle || 'Confirm action';
                message.textContent = form.dataset.confirmMessage || 'Please confirm this action.';
                confirmButton.textContent = form.dataset.confirmButton || 'Confirm';
                confirmButton.dataset.variant = form.dataset.confirmVariant || 'danger';
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                confirmButton.focus();
            };

            document.addEventListener('submit', (event) => {
                const form = event.target;

                if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
                    return;
                }

                if (form.dataset.confirming === 'true') {
                    delete form.dataset.confirming;
                    return;
                }

                event.preventDefault();
                openModal(form);
            }, true);

            cancelButton.addEventListener('click', closeModal);

            confirmButton.addEventListener('click', () => {
                if (!pendingForm) {
                    closeModal();
                    return;
                }

                const form = pendingForm;
                form.dataset.confirming = 'true';
                closeModal();
                form.submit();
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (!modal.classList.contains('open')) {
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeModal();
                }
            });
        })();
    </script>
</body>

</html>
