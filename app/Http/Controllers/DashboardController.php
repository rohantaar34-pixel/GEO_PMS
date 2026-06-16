<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics for the dashboard
        $stats = [
            'total_projects' => Project::count(),
            'total_documents' => Document::count(), // You'll need to create this model
            'total_budget' => Project::sum('budget'),
        ];
        
        // Get all projects for the dashboard to display their current budgets
        $projects = Project::orderBy('created_at', 'desc')->get();

        return view('dashboard', compact('stats', 'projects'));
    }
}