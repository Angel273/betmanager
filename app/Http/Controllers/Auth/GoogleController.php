<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AllowedEmail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Error al autenticar con Google. Inténtalo de nuevo.');
        }

        $email = $googleUser->getEmail();

        // Check if email is in the allowed list
        $isAllowed = AllowedEmail::where('email', $email)->exists();

        if (!$isAllowed) {
            return redirect()->route('login')->with('error', 'Acceso denegado. Tu correo electrónico (' . $email . ') no está registrado en la lista de accesos permitidos.');
        }

        // Find or create the user
        $user = User::where('email', $email)->first();

        if ($user) {
            // Update Google ID and Avatar if needed
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        } else {
            // Create user
            // The first user registered in the system will automatically be an Admin
            $isFirstUser = User::count() === 0;

            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'is_admin' => $isFirstUser, // First user is admin
                'password' => null, // Google authenticated users don't need a password
            ]);
        }

        // Log in
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
