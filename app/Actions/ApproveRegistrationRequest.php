<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveRegistrationRequest
{
    public function execute(RegistrationRequest $registrationRequest): User
    {
        return DB::transaction(function () use ($registrationRequest): User {
            $request = RegistrationRequest::query()->lockForUpdate()->findOrFail($registrationRequest->id);

            $user = User::query()->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => UserRole::User,
                'is_active' => true,
            ]);

            $request->delete();

            return $user;
        });
    }
}
