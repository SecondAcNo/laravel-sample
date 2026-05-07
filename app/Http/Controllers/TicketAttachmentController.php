<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketAttachmentRequest;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function store(
        StoreTicketAttachmentRequest $request,
        Ticket $ticket,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $this->authorize('attach', $ticket);

        $file = $request->file('attachment');
        $storedPath = $file->store('ticket_attachments');

        $attachment = $ticket->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        $auditLogService->record($request, 'attachment added', $attachment, null, $attachment->only([
            'ticket_id',
            'uploaded_by',
            'original_name',
            'stored_path',
            'mime_type',
            'size',
        ]));

        return redirect()
            ->route($this->redirectRouteName($request), $ticket)
            ->with('status', 'Attachment uploaded.');
    }

    public function download(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        $attachment->load('ticket');

        $this->authorize('downloadAttachment', $attachment->ticket);

        abort_unless(Storage::exists($attachment->stored_path), 404);

        return Storage::download($attachment->stored_path, $attachment->original_name);
    }

    private function redirectRouteName(Request $request): string
    {
        return match (true) {
            $request->user()->isAdmin() => 'admin.tickets.show',
            $request->user()->isSupportAgent() => 'agent.tickets.show',
            default => 'tickets.show',
        };
    }
}
