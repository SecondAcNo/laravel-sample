<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $ownTickets = Ticket::query()
            ->where('requester_id', $user->id);

        return view('dashboard', [
            'openTicketCount' => (clone $ownTickets)
                ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                ->count(),
            'waitingUserCount' => (clone $ownTickets)
                ->where('status', Ticket::STATUS_WAITING_USER)
                ->count(),
            'resolvedTicketCount' => (clone $ownTickets)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->count(),
            'recentTickets' => (clone $ownTickets)
                ->with(['category', 'assignee'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
