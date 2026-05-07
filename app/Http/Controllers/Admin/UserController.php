<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['q', 'role', 'department_id']);

        return view('admin.users.index', [
            'users' => User::query()
                ->with('department')
                ->filter($filters)
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'departments' => Department::query()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $user = User::create($request->validated());

        $auditLogService->record($request, 'user created', $user, null, $user->only([
            'name',
            'email',
            'role',
            'department_id',
        ]));

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user,
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogService $auditLogService): RedirectResponse
    {
        $beforeValues = $user->only([
            'name',
            'email',
            'role',
            'department_id',
        ]);

        $user->update($request->validated());

        $afterValues = $user->only([
            'name',
            'email',
            'role',
            'department_id',
        ]);
        $action = $beforeValues['role'] !== $afterValues['role']
            ? 'user role changed'
            : 'user updated';

        $auditLogService->record($request, $action, $user, $beforeValues, $afterValues);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated.');
    }
}
