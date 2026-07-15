<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveRegistrationRequest
{
    public function execute(RegistrationRequest $registrationRequest): User
    {
        try {
            return DB::transaction(function () use ($registrationRequest): User {
                $request = RegistrationRequest::query()->lockForUpdate()->findOrFail($registrationRequest->id);

                if (User::query()->where('email', $request->email)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Ezzel az e-mail-címmel már létezik felhasználó.',
                    ]);
                }

                $user = User::query()->create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'role' => UserRole::User,
                    'is_active' => true,
                ]);

                $request->delete();

                return $user;
            }, attempts: 5);
        } catch (QueryException $exception) {
            if (User::query()->where('email', $registrationRequest->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Ezzel az e-mail-címmel már létezik felhasználó.',
                ]);
            }

            throw $exception;
        }
    }
}
