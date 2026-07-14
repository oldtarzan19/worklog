<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $removesActiveAdmin = $lockedUser->role === UserRole::Admin
                && $lockedUser->is_active
                && ($data['role'] !== UserRole::Admin->value || ! $data['is_active']);

            if ($removesActiveAdmin && User::query()->where('role', UserRole::Admin)->where('is_active', true)->lockForUpdate()->get(['id'])->count() === 1) {
                throw ValidationException::withMessages(['role' => 'Az utolsó aktív adminisztrátor nem tiltható le és nem fokozható le.']);
            }

            $lockedUser->update($data);
        });

        return back()->with('success', 'A felhasználó adatai frissültek.');
    }
}
