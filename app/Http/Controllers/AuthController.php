<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response|RedirectResponse
    {
        if ($request->session()->get('passcode_ok') === true) {
            return redirect()->route('home');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'passcode' => ['required', 'string'],
        ]);

        $expected = (string) Config::get('app.passcode', '');

        if ($expected === '' || ! hash_equals($expected, $data['passcode'])) {
            return back()->withErrors(['passcode' => 'Invalid passcode.']);
        }

        $request->session()->regenerate();
        $request->session()->put('passcode_ok', true);

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('passcode_ok');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
