<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $ticket->ticket_no }}
            </h2>
            <div class="flex items-center gap-4">
                @can('update', $ticket)
                    <a href="{{ route('tickets.edit', $ticket) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                        編集
                    </a>
                @endcan
                <a href="{{ route('tickets.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    チケット一覧へ戻る
                </a>
            </div>
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
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">カテゴリ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->category->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">種別</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ [
                            'access_request' => '権限申請',
                            'incident' => '障害報告',
                            'general_request' => '通常依頼',
                        ][$ticket->type] ?? $ticket->type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">ステータス</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ [
                            'open' => '未対応',
                            'triaged' => '確認済み',
                            'in_progress' => '対応中',
                            'waiting_user' => '申請者確認待ち',
                            'resolved' => '解決済み',
                            'closed' => 'クローズ',
                        ][$ticket->status] ?? $ticket->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">優先度</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ [
                            'low' => '低',
                            'normal' => '通常',
                            'high' => '高',
                            'urgent' => '緊急',
                        ][$ticket->priority] ?? $ticket->priority }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">申請者</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->requester->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">担当者</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->assignee?->name ?? '未割当' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">作成日時</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">クローズ日時</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ticket->closed_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            @if ($ticket->status === \App\Models\Ticket::STATUS_RESOLVED)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">チケットをクローズ</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        このチケットは解決済みです。依頼内容が完了していればクローズできます。
                    </p>

                    <form method="POST" action="{{ route('tickets.close', $ticket) }}" class="mt-4">
                        @csrf
                        @method('PATCH')

                        <x-primary-button>クローズ</x-primary-button>
                    </form>
                </div>
            @endif

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">添付ファイル</h3>

                <form method="POST" action="{{ route('tickets.attachments.store', $ticket) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf

                    <div class="w-full">
                        <x-input-label for="attachment" value="ファイルを選択" />
                        <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm text-gray-700" required>
                        <x-input-error class="mt-2" :messages="$errors->get('attachment')" />
                    </div>

                    <x-primary-button>アップロード</x-primary-button>
                </form>

                <div class="mt-6 space-y-3">
                    @forelse ($ticket->attachments as $attachment)
                        <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                            <div>
                                <a href="{{ route('ticket-attachments.download', $attachment) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $attachment->original_name }}
                                </a>
                                <p class="mt-1 text-xs text-gray-500">
                                    アップロード者: {{ $attachment->uploader->name }} ・ {{ number_format($attachment->size / 1024, 1) }} KB
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">添付ファイルはまだありません。</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">コメント</h3>

                <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="mt-4 space-y-3">
                    @csrf

                    <div>
                        <x-input-label for="body" value="コメントを追加" />
                        <textarea id="body" name="body" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('body') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('body')" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>投稿</x-primary-button>
                    </div>
                </form>

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
                        <p class="text-sm text-gray-500">コメントはまだありません。</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
