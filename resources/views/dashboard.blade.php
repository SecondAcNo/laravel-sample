<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">未対応チケット</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $openTicketCount }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">確認待ち</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $waitingUserCount }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">解決済み</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $resolvedTicketCount }}</p>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">最近のチケット</h3>
                    <div>
                        <a href="{{ route('tickets.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            チケット一覧
                        </a>
                        <a href="{{ route('tickets.create') }}" class="ms-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            チケット作成
                        </a>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recentTickets as $ticket)
                                <tr>
                                    <td class="py-3 pe-4 text-sm font-medium">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $ticket->ticket_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $ticket->title }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->category->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->assignee?->name ?? '未割当' }}</td>
                                    <td class="py-3 ps-4 text-sm text-gray-500">{{ str_replace('_', ' ', $ticket->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-6 text-sm text-gray-500">まだチケットはありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
