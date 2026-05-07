<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $ticket->ticket_no }}
            </h2>
            <a href="{{ route('agent.tickets.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                Back to agent tickets
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $ticket->title }}</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $ticket->description }}</p>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->category->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Requester</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->requester->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Assignee</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->assignee?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', $ticket->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Priority</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->priority }}</dd>
                    </div>
                </dl>

                @if ($ticket->assignee_id === null)
                    <form method="POST" action="{{ route('agent.tickets.claim.update', $ticket) }}" class="mt-6">
                        @csrf
                        @method('PATCH')

                        <x-primary-button>Claim Ticket</x-primary-button>
                    </form>
                @endif
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">Status Update</h3>

                @if ($availableStatuses)
                    <form method="POST" action="{{ route('agent.tickets.status.update', $ticket) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        @method('PATCH')

                        <div class="w-full sm:max-w-xs">
                            <x-input-label for="status" value="Next Status" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @foreach ($availableStatuses as $status)
                                    <option value="{{ $status }}" @selected(old('status') === $status)>
                                        {{ str_replace('_', ' ', $status) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>

                        <x-primary-button>Update Status</x-primary-button>
                    </form>
                @else
                    <p class="mt-4 text-sm text-gray-500">No status changes are available.</p>
                @endif
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">Attachments</h3>

                @can('attach', $ticket)
                    <form method="POST" action="{{ route('tickets.attachments.store', $ticket) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf

                        <div class="w-full">
                            <x-input-label for="attachment" value="Upload File" />
                            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm text-gray-700" required>
                            <x-input-error class="mt-2" :messages="$errors->get('attachment')" />
                        </div>

                        <x-primary-button>Upload</x-primary-button>
                    </form>
                @endcan

                <div class="mt-6 space-y-3">
                    @forelse ($ticket->attachments as $attachment)
                        <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                            <div>
                                <a href="{{ route('ticket-attachments.download', $attachment) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $attachment->original_name }}
                                </a>
                                <p class="mt-1 text-xs text-gray-500">
                                    Uploaded by {{ $attachment->uploader->name }} ・ {{ number_format($attachment->size / 1024, 1) }} KB
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No attachments yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">Comments</h3>

                @can('comment', $ticket)
                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="mt-4 space-y-3">
                        @csrf

                        <div>
                            <x-input-label for="body" value="Add Comment" />
                            <textarea id="body" name="body" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('body') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('body')" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>Post Comment</x-primary-button>
                        </div>
                    </form>
                @endcan

                <div class="mt-6 space-y-4">
                    @forelse ($ticket->comments as $comment)
                        <div class="border-t border-gray-100 pt-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $comment->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $comment->body }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No comments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
