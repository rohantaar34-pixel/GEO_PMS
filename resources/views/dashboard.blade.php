@extends('layouts.app')

@section('content')
    <style>
        :root {
            --ledger: #6366f1;
            --document: #059669;
            --project: #ea580c;
            --ink: #111827;
            --ink-2: #374151;
            --ink-3: #6b7280;
            --border: #e8e8ed;
            --bg: #f8f8fb;
            --white: #ffffff;
            --radius: 16px;
            --radius-sm: 12px;
        }

        * {
            box-sizing: border-box;
        }

        .dashboard-wrap {
            min-height: calc(100vh - 80px);
            background: var(--bg);
            padding: 40px 24px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Welcome Section */
        .welcome-section {
            margin-bottom: 48px;
            text-align: center;
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 16px;
            color: var(--ink-3);
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .choice-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px 24px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: block;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .choice-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .choice-card:active {
            transform: translateY(0);
        }

        /* Card Icons */
        .card-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .ledger-icon {
            background: rgba(99, 102, 241, 0.1);
            color: var(--ledger);
        }

        .document-icon {
            background: rgba(5, 150, 105, 0.1);
            color: var(--document);
        }

        .project-icon {
            background: rgba(234, 88, 12, 0.1);
            color: var(--project);
        }

        /* Card Content */
        .card-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--ink);
        }

        .card-description {
            font-size: 14px;
            color: var(--ink-3);
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .card-footer {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .ledger-text {
            color: var(--ledger);
        }

        .document-text {
            color: var(--document);
        }

        .project-text {
            color: var(--project);
        }

        .arrow {
            transition: transform 0.2s ease;
        }

        .choice-card:hover .arrow {
            transform: translateX(4px);
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 48px;
            padding-top: 48px;
            border-top: 1px solid var(--border);
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: var(--white);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Project Budgets Section */
        .project-budgets-section {
            margin-top: 48px;
            padding-top: 48px;
            border-top: 1px solid var(--border);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 24px;
        }

        .project-budget-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .project-budget-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .project-budget-card:hover {
            border-color: #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
            transform: translateY(-2px);
        }

        .pb-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .pb-status {
            font-size: 12px;
            color: var(--ink-3);
            text-transform: capitalize;
        }

        .pb-amount {
            text-align: right;
        }

        .pb-balance {
            font-size: 18px;
            font-weight: 800;
            color: #059669; /* Green for positive balance */
        }
        
        .pb-balance.negative {
            color: #dc2626; /* Red for negative balance */
        }

        .pb-label {
            font-size: 11px;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }

        /* Logout Section */
        .logout-section {
            text-align: center;
            margin-top: 48px;
            padding-top: 48px;
            border-top: 1px solid var(--border);
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--ink-3);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #dc2626;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .dashboard-wrap {
                padding: 24px 16px;
            }

            .welcome-title {
                font-size: 28px;
            }

            .cards-grid {
                gap: 16px;
            }

            .choice-card {
                padding: 24px 20px;
            }

            .card-title {
                font-size: 20px;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .choice-card {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .choice-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .choice-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .choice-card:nth-child(3) {
            animation-delay: 0.3s;
        }
    </style>

    <div class="dashboard-wrap">
        <div class="dashboard-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">Welcome back, {{ Auth::user()->name ?? 'User' }}!</h1>
                <p class="welcome-subtitle">Choose a module to get started</p>
            </div>

            <!-- Choice Cards -->
            <div class="cards-grid">
                <!-- Ledger Card -->
                <a href="{{ route('projects.index') }}" class="choice-card">
                    <div class="card-icon ledger-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" />
                            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                            <line x1="12" y1="11" x2="12" y2="17" />
                            <line x1="9" y1="14" x2="15" y2="14" />
                        </svg>
                    </div>
                    <h2 class="card-title">Ledger</h2>
                    <p class="card-description">
                        Manage project finances, track budgets, monitor expenses, and view financial summaries.
                    </p>
                    <div class="card-footer">
                        <span class="ledger-text">Access Ledger</span>
                        <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </div>
                </a>

                <!-- Document Tracker Card -->
                <a href="{{ route('documents.index') }}" class="choice-card">
                    <div class="card-icon document-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <polyline points="10 9 9 9 8 9" />
                        </svg>
                    </div>
                    <h2 class="card-title">Document Tracker</h2>
                    <p class="card-description">
                        Organize and track important documents, manage files, and maintain document records.
                    </p>
                    <div class="card-footer">
                        <span class="document-text">Access Documents</span>
                        <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </div>
                </a>

                <!-- Project Monitoring Card -->
                <a href="{{ route('monitoring.index') }}" class="choice-card">
                    <div class="card-icon project-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                            <polyline points="3.29 7 12 12 20.71 7" />
                            <line x1="12" y1="22" x2="12" y2="12" />
                        </svg>
                    </div>
                    <h2 class="card-title">Project Monitoring</h2>
                    <p class="card-description">
                        Track project progress, milestones, tasks, and overall project performance metrics.
                    </p>
                    <div class="card-footer">
                        <span class="project-text">Monitor Projects</span>
                        <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </div>
                </a>
            </div>

            <div class="cards-grid" style="margin-top: 24px;">
                <!-- Settings Card -->
                <a href="{{ route('settings.projects.index') }}" class="choice-card" style="animation-delay: 0.4s;">
                    <div class="card-icon" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                    </div>
                    <h2 class="card-title">Settings</h2>
                    <p class="card-description">
                        Manage central settings, including project creation, updates, and general application configuration.
                    </p>
                    <div class="card-footer">
                        <span style="color: #4f46e5;">Access Settings</span>
                        <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </div>
                </a>
            </div>

            <!-- Quick Stats Section -->
            <div class="stats-section">
                <div class="stat-item">
                    <div class="stat-number">{{ $stats['total_projects'] ?? 0 }}</div>
                    <div class="stat-label">Active Projects</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ $stats['total_documents'] ?? 0 }}</div>
                    <div class="stat-label">Documents</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">₱{{ number_format($stats['total_budget'] ?? 0, 0) }}</div>
                    <div class="stat-label">Total Initial Budget</div>
                </div>
            </div>

            <!-- Project Budgets Section -->
            @if(isset($projects) && $projects->count() > 0)
            <div class="project-budgets-section">
                <h2 class="section-title">Current Budget by Project</h2>
                <div class="project-budget-list">
                    @foreach($projects as $project)
                        @php
                            $currentBudget = $project->current_budget;
                        @endphp
                        <a href="{{ route('projects.show', $project) }}" class="project-budget-card">
                            <div>
                                <div class="pb-name">{{ $project->name }}</div>
                                <div class="pb-status">{{ $project->status ?? 'Active' }}</div>
                            </div>
                            <div class="pb-amount">
                                <div class="pb-balance {{ $currentBudget < 0 ? 'negative' : '' }}">
                                    ₱{{ number_format($currentBudget, 2) }}
                                </div>
                                <div class="pb-label">Current Balance</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Logout Section -->
            <div class="logout-section">
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
