<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            チケット編集
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-6 p-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="title" value="件名" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $ticket->title)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="category_id" value="カテゴリ" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">カテゴリを選択</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $ticket->category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="type" value="種別" />
                            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="access_request" @selected(old('type', $ticket->type) === 'access_request')>権限申請</option>
                                <option value="incident" @selected(old('type', $ticket->type) === 'incident')>障害報告</option>
                                <option value="general_request" @selected(old('type', $ticket->type) === 'general_request')>通常依頼</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <div>
                            <x-input-label for="priority" value="優先度" />
                            <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="low" @selected(old('priority', $ticket->priority) === 'low')>低</option>
                                <option value="normal" @selected(old('priority', $ticket->priority) === 'normal')>通常</option>
                                <option value="high" @selected(old('priority', $ticket->priority) === 'high')>高</option>
                                <option value="urgent" @selected(old('priority', $ticket->priority) === 'urgent')>緊急</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('priority')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="説明" />
                        <textarea id="description" name="description" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('description', $ticket->description) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('tickets.show', $ticket) }}" class="text-sm text-gray-600 hover:text-gray-900">キャンセル</a>
                        <x-primary-button>更新</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
