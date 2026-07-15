<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegistrationRequest;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    public function pending(): Response
    {
        return Inertia::render('auth/RegistrationPending');
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $normalizedEmail = $request->string('email')->lower()->toString();

        return Cache::lock('worklog:registration:'.hash('sha256', $normalizedEmail), 10)->block(5, function () use ($request, $normalizedEmail): RedirectResponse {
            return DB::transaction(function () use ($request, $normalizedEmail): RedirectResponse {
                if (User::query()->where('email', $request->string('email'))->exists()
                    || RegistrationRequest::query()->where('email', $request->string('email'))->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Ezzel az e-mail-címmel már létezik fiók vagy függő kérelem.',
                    ]);
                }

                $attributes = [
                    'name' => $request->string('name'),
                    'email' => $normalizedEmail,
                    'password' => Hash::make($request->string('password')),
                ];

                RegistrationRequest::query()->create($attributes);

                return to_route('registration.pending')->with('success', 'A regisztrációd jóváhagyásra vár.');
            });
        });
    }
}
