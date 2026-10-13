<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    /**
     * Exchange field-officer credentials for a Sanctum token, applying the same account rules as the web login.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact the administrator.'],
            ]);
        }

        if ($user && $user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            $minutesLeft = (int) ceil(Carbon::now()->diffInMinutes(Carbon::parse($user->locked_until)));

            throw ValidationException::withMessages([
                'email' => ["Account is temporarily locked due to too many failed attempts. Try again in {$minutesLeft} minutes."],
            ]);
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $attempts = $user->failed_login_attempts + 1;
                $user->update([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $attempts >= 5 ? now()->addMinutes(15) : null,
                ]);
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        AuditLog::log('logged_in_mobile', $user, null, ['ip' => $request->ip(), 'device' => $credentials['device_name']]);

        return response()->json([
            'token' => $user->createToken($credentials['device_name'])->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Current officer profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /**
     * Revoke the token used by this device.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * @return array{id: int, name: string, email: string, roles: array<int, string>, tehsils: array<int, array{id: int, name: string}>}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values()->all(),
            'tehsils' => $user->tehsils()->orderBy('name')->get(['tehsils.id', 'tehsils.name'])
                ->map(fn ($tehsil) => ['id' => $tehsil->id, 'name' => $tehsil->name])
                ->all(),
        ];
    }
}
