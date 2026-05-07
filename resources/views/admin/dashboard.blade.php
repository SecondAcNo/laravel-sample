<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            管理ダッシュボード
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">総チケット数</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalTickets }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">未対応</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $openTickets }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">緊急</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $urgentTickets }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">未割当</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $unassignedTickets }}</p>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">最近のチケット</h3>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">ユーザー</a>
                        <a href="{{ route('admin.categories.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">カテゴリ</a>
                        <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">監査ログ</a>
                        <a href="{{ route('admin.tickets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">すべて表示</a>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recentTickets as $ticket)
                                <tr>
                                    <td class="py-3 pe-4 text-sm font-medium">
                                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $ticket->ticket_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $ticket->title }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->requester->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->assignee?->name ?? '未割当' }}</td>
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
                                    <td class="py-6 text-sm text-gray-500">まだチケットはありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">担当者別チケット</h3>
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">ユーザー</a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="py-2 pe-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">担当者</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">割当数</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">対応中</th>
                                    <th class="py-2 ps-4 text-right text-xs font-medium uppercase tracking-wider text-gray-500">緊急</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($assigneeSummaries as $assignee)
                                    <tr>
                                        <td class="py-3 pe-4 text-sm font-medium text-gray-900">{{ $assignee->name }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-500">{{ $assignee->assigned_ticket_count }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-500">{{ $assignee->active_ticket_count }}</td>
                                        <td class="py-3 ps-4 text-right text-sm text-gray-500">{{ $assignee->urgent_ticket_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-sm text-gray-500">IT担当者がまだ登録されていません。</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">カテゴリ別チケット</h3>
                        <a href="{{ route('admin.categories.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">カテゴリ</a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="py-2 pe-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">カテゴリ</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">合計</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">未対応</th>
                                    <th class="py-2 ps-4 text-right text-xs font-medium uppercase tracking-wider text-gray-500">緊急</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($categorySummaries as $category)
                                    <tr>
                                        <td class="py-3 pe-4 text-sm font-medium text-gray-900">{{ $category->name }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-500">{{ $category->ticket_count }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-500">{{ $category->open_ticket_count }}</td>
                                        <td class="py-3 ps-4 text-right text-sm text-gray-500">{{ $category->urgent_ticket_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-sm text-gray-500">カテゴリがまだありません。</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
