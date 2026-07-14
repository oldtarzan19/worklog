<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApproveRegistrationRequest;
use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Registrations', [
            'requests' => RegistrationRequest::query()->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function approve(RegistrationRequest $registrationRequest, ApproveRegistrationRequest $approve): RedirectResponse
    {
        $approve->execute($registrationRequest);

        return back()->with('success', 'A regisztráció jóváhagyva.');
    }

    public function reject(RegistrationRequest $registrationRequest): RedirectResponse
    {
        $registrationRequest->delete();

        return back()->with('success', 'A regisztrációs kérelem elutasítva.');
    }
}
