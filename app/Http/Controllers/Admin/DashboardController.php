<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('admin.dashboard', [
            'totalTickets' => Ticket::query()->count(),
            'openTickets' => Ticket::query()->where('status', Ticket::STATUS_OPEN)->count(),
            'urgentTickets' => Ticket::query()->where('priority', Ticket::PRIORITY_URGENT)->count(),
            'unassignedTickets' => Ticket::query()->whereNull('assignee_id')->count(),
            'recentTickets' => Ticket::query()
                ->with(['category', 'requester', 'assignee'])
                ->latest()
                ->limit(5)
                ->get(),
            'assigneeSummaries' => User::query()
                ->where('role', 'support_agent')
                ->withCount([
                    'assignedTickets as assigned_ticket_count',
                    'assignedTickets as active_ticket_count' => fn ($query) => $query
                        ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]),
                    'assignedTickets as urgent_ticket_count' => fn ($query) => $query
                        ->where('priority', Ticket::PRIORITY_URGENT),
                ])
                ->orderBy('name')
                ->get(),
            'categorySummaries' => Category::query()
                ->withCount([
                    'tickets as ticket_count',
                    'tickets as open_ticket_count' => fn ($query) => $query
                        ->where('status', Ticket::STATUS_OPEN),
                    'tickets as urgent_ticket_count' => fn ($query) => $query
                        ->where('priority', Ticket::PRIORITY_URGENT),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
