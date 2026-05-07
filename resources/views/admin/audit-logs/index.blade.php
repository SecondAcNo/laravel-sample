<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            監査ログ
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <x-input-label for="q" value="キーワード" />
                        <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$filters['q'] ?? ''" placeholder="操作、ユーザー、対象" />
                    </div>
                    <div>
                        <x-input-label for="action" value="操作" />
                        <select id="action" name="action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">すべて</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="target_type" value="対象" />
                        <select id="target_type" name="target_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">すべて</option>
                            @foreach ($targetTypes as $targetType)
                                <option value="{{ $targetType }}" @selected(($filters['target_type'] ?? '') === $targetType)>{{ class_basename($targetType) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button>検索</x-primary-button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">クリア</a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">日時</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ユーザー</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">操作</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">対象</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($auditLogs as $auditLog)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $auditLog->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ $auditLog->user?->name ?? 'システム' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $auditLog->action }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ class_basename($auditLog->target_type) }} #{{ $auditLog->target_id }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $auditLog->ip_address ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                        監査ログはまだありません。
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $auditLogs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
