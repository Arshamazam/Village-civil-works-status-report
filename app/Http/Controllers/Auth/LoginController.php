<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if ($request->has('logout') || $request->has('switch')) {
            if (Auth::check()) {
                AuditLog::log('logged_out', Auth::user());
                Auth::logout();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        $currentUser = Auth::user();

        return view('auth.login', compact('currentUser'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user) {
            // Check if user is inactive (AUTH-7)
            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been deactivated. Please contact the administrator.'],
                ]);
            }

            // Check if locked out (AUTH-4: 5 failed attempts -> 15 min lock)
            if ($user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
                $minutesLeft = (int) ceil(Carbon::now()->diffInMinutes(Carbon::parse($user->locked_until)));
                throw ValidationException::withMessages([
                    'email' => ["Account is temporarily locked due to too many failed attempts. Try again in {$minutesLeft} minutes."],
                ]);
            }

            // Verify password
            if (Hash::check($credentials['password'], $user->password)) {
                // Reset failed attempts
                $user->update([
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'last_login_at' => now(),
                ]);

                $remember = $request->boolean('remember', true); // default true for 30 days (AUTH-5)
                Auth::login($user, $remember);
                $request->session()->regenerate();

                AuditLog::log('logged_in', $user, null, ['ip' => $request->ip()]);

                if ($user->force_password_change) {
                    return redirect()->route('password.force_change');
                }

                return $this->redirectBasedOnRole($user);
            } else {
                // Increment failed attempts
                $attempts = $user->failed_login_attempts + 1;
                $lockedUntil = null;
                if ($attempts >= 5) {
                    $lockedUntil = now()->addMinutes(15);
                }

                $user->update([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $lockedUntil,
                ]);

                if ($attempts >= 5) {
                    throw ValidationException::withMessages([
                        'email' => ['Too many failed login attempts. Your account has been locked for 15 minutes.'],
                    ]);
                }
            }
        }

        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('logged_out', Auth::user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForceChangeForm()
    {
        return view('auth.force_change');
    }

    public function updateForceChangePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->password),
            'force_password_change' => false,
        ]);

        AuditLog::log('forced_password_changed', $user);

        return $this->redirectBasedOnRole($user)->with('success', 'Password updated successfully.');
    }

    protected function redirectBasedOnRole(User $user)
    {
        if ($user->hasRole('Field Officer')) {
            return redirect()->route('field.today');
        }

        if ($user->hasRole('Tehsil In-charge')) {
            return redirect()->route('review.index');
        }

        return redirect()->route('dashboard');
    }
}
