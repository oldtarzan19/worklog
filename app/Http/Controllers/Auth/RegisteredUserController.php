<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegistrationRequest;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register', [
            'isFirstRegistration' => ! User::query()->exists() && ! RegistrationRequest::query()->exists(),
        ]);
    }

    public function pending(): Response
    {
        return Inertia::render('auth/RegistrationPending');
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        return Cache::lock('worklog:first-registration', 10)->block(5, function () use ($request): RedirectResponse {
            return DB::transaction(function () use ($request): RedirectResponse {
                $isFirstRegistration = ! User::query()->exists() && ! RegistrationRequest::query()->exists();
                $attributes = [
                    'name' => $request->string('name'),
                    'email' => $request->string('email'),
                    'password' => Hash::make($request->string('password')),
                ];

                if ($isFirstRegistration) {
                    $user = User::query()->create([
                        ...$attributes,
                        'role' => UserRole::Admin,
                        'is_active' => true,
                    ]);
                    Auth::login($user);

                    return to_route('dashboard')->with('success', 'Az első fiók adminisztrátorként létrejött.');
                }

                RegistrationRequest::query()->create($attributes);

                return to_route('registration.pending')->with('success', 'A regisztrációd jóváhagyásra vár.');
            });
        });
    }
}
