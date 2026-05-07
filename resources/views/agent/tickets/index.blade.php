<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Agent Tickets
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('agent.tickets.index') }}" class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <div class="grid gap-4 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <x-input-label for="q" value="Keyword" />
                        <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$filters['q'] ?? ''" placeholder="Ticket no, title, description" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ([App\Models\Ticket::STATUS_OPEN, App\Models\Ticket::STATUS_TRIAGED, App\Models\Ticket::STATUS_IN_PROGRESS, App\Models\Ticket::STATUS_WAITING_USER, App\Models\Ticket::STATUS_RESOLVED, App\Models\Ticket::STATUS_CLOSED] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="priority" value="Priority" />
                        <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ([App\Models\Ticket::PRIORITY_LOW, App\Models\Ticket::PRIORITY_NORMAL, App\Models\Ticket::PRIORITY_HIGH, App\Models\Ticket::PRIORITY_URGENT] as $priority)
                                <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ $priority }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="assignee" value="Assignment" />
                        <select id="assignee" name="assignee" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All visible</option>
                            <option value="assigned" @selected(($filters['assignee'] ?? '') === 'assigned')>Assigned to me</option>
                            <option value="unassigned" @selected(($filters['assignee'] ?? '') === 'unassigned')>Unassigned</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="category_id" value="Category" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button>Search</x-primary-button>
                    <a href="{{ route('agent.tickets.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Clear</a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Requester</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assignee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        <a href="{{ route('agent.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $ticket->ticket_no }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $ticket->title }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $ticket->requester->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ str_replace('_', ' ', $ticket->status) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $ticket->priority }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $ticket->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No tickets available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
