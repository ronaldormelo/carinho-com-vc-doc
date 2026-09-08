<?php

namespace App\Http\Controllers;

use App\Services\CaregiverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(
        private CaregiverService $caregiverService
    ) {}

    public function form(): View
    {
        return view('cadastro');
    }

    public function store(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email|max:255',
            'city' => 'required|string|max:128',
            'experience_years' => 'nullable|integer|min:0',
            'profile_summary' => 'nullable|string|max:2000',
        ]);

        $caregiver = $this->caregiverService->create($validated);

        return redirect()
            ->route('confirmacao')
            ->with('caregiver_id', $caregiver->id)
            ->with('caregiver_name', $caregiver->name);
    }

    public function confirmation(): View
    {
        return view('confirmacao');
    }
}
