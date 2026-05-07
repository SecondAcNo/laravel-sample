<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            全チケット
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <div class="grid gap-4 md:grid-cols-5">
                    <div class="md:col-span-2">
                        <x-input-label for="q" value="キーワード" />
                        <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$filters['q'] ?? ''" placeholder="チケット番号、件名、説明" />
                    </div>
                    <div>
                        <x-input-label for="status" value="ステータス" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">すべて</option>
                            @foreach ([App\Models\Ticket::STATUS_OPEN, App\Models\Ticket::STATUS_TRIAGED, App\Models\Ticket::STATUS_IN_PROGRESS, App\Models\Ticket::STATUS_WAITING_USER, App\Models\Ticket::STATUS_RESOLVED, App\Models\Ticket::STATUS_CLOSED] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ [
                                    'open' => '未対応',
                                    'triaged' => '確認済み',
                                    'in_progress' => '対応中',
                                    'waiting_user' => '申請者確認待ち',
                                    'resolved' => '解決済み',
                                    'closed' => 'クローズ',
                                ][$status] ?? $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="priority" value="優先度" />
                        <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">すべて</option>
                            @foreach ([App\Models\Ticket::PRIORITY_LOW, App\Models\Ticket::PRIORITY_NORMAL, App\Models\Ticket::PRIORITY_HIGH, App\Models\Ticket::PRIORITY_URGENT] as $priority)
                                <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ [
                                    'low' => '低',
                                    'normal' => '通常',
                                    'high' => '高',
                                    'urgent' => '緊急',
                                ][$priority] ?? $priority }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="category_id" value="カテゴリ" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">すべて</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button>検索</x-primary-button>
                    <a href="{{ route('admin.tickets.index') }}" class="text-sm text-gray-600 hover:text-gray-900">クリア</a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">番号</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">件名</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">申請者</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">担当者</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ステータス</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">優先度</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $ticket->ticket_no }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $ticket->title }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $ticket->requester->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $ticket->assignee?->name ?? '未割当' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ [
                                        'open' => '未対応',
                                        'triaged' => '確認済み',
                                        'in_progress' => '対応中',
                                        'waiting_user' => '申請者確認待ち',
                                        'resolved' => '解決済み',
                                        'closed' => 'クローズ',
                                    ][$ticket->status] ?? $ticket->status }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ [
                                        'low' => '低',
                                        'normal' => '通常',
                                        'high' => '高',
                                        'urgent' => '緊急',
                                    ][$ticket->priority] ?? $ticket->priority }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                        まだチケットはありません。
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
