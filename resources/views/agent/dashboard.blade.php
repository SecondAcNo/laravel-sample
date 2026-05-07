<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            担当者ダッシュボード
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">未対応</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $openCount }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">対応中</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $inProgressCount }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">緊急</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $urgentCount }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">未割当</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $unassignedCount }}</p>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">最近の担当チケット</h3>
                    <a href="{{ route('agent.tickets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">すべて表示</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recentTickets as $ticket)
                                <tr>
                                    <td class="py-3 pe-4 text-sm font-medium">
                                        <a href="{{ route('agent.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $ticket->ticket_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $ticket->title }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->requester->name }}</td>
                                    <td class="py-3 ps-4 text-sm text-gray-500">{{ [
                                        'open' => '未対応',
                                        'triaged' => '確認済み',
                                        'in_progress' => '対応中',
                                        'waiting_user' => '申請者確認待ち',
                                        'resolved' => '解決済み',
                                        'closed' => 'クローズ',
                                    ][$ticket->status] ?? $ticket->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-6 text-sm text-gray-500">担当チケットはありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">最近の未割当チケット</h3>
                    <a href="{{ route('agent.tickets.index', ['assignee' => 'unassigned']) }}" class="text-sm text-indigo-600 hover:text-indigo-900">未割当一覧</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recentUnassignedTickets as $ticket)
                                <tr>
                                    <td class="py-3 pe-4 text-sm font-medium">
                                        <a href="{{ route('agent.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $ticket->ticket_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $ticket->title }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->requester->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->category->name }}</td>
                                    <td class="py-3 ps-4 text-sm text-gray-500">{{ [
                                        'low' => '低',
                                        'normal' => '通常',
                                        'high' => '高',
                                        'urgent' => '緊急',
                                    ][$ticket->priority] ?? $ticket->priority }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-6 text-sm text-gray-500">未割当チケットはありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
