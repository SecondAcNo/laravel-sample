<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->with(['category', 'assignee'])
            ->where('requester_id', $request->user()->id)
            ->filter($request->only(['q', 'status', 'priority', 'type', 'category_id']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'priority', 'type', 'category_id']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.create', [
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreTicketRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = Ticket::create([
            ...$request->validated(),
            'ticket_no' => $this->generateTicketNo(),
            'status' => Ticket::STATUS_OPEN,
            'requester_id' => $request->user()->id,
            'assignee_id' => null,
            'closed_at' => null,
        ]);

        $auditLogService->record($request, 'ticket created', $ticket, null, $ticket->only([
            'ticket_no',
            'title',
            'type',
            'status',
            'priority',
            'category_id',
            'requester_id',
            'assignee_id',
        ]));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket created.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['category', 'requester', 'assignee', 'comments.user', 'attachments.uploader']);

        return view('tickets.show', [
            'ticket' => $ticket,
        ]);
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorize('update', $ticket);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, AuditLogService $auditLogService): RedirectResponse
    {
        $beforeValues = $ticket->only([
            'title',
            'description',
            'type',
            'priority',
            'category_id',
        ]);

        $ticket->update($request->validated());

        $auditLogService->record(
            $request,
            'ticket updated',
            $ticket,
            $beforeValues,
            $ticket->only([
                'title',
                'description',
                'type',
                'priority',
                'category_id',
            ]),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket updated.');
    }

    private function generateTicketNo(): string
    {
        $prefix = 'TKT-'.now()->format('Ymd').'-';
        $count = Ticket::query()
            ->where('ticket_no', 'like', $prefix.'%')
            ->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
