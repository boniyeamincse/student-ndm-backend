<?php

namespace App\Services;

use App\Enum\UserStatus;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Spatie\Permission\Models\Role;

class AuthService
{
    /**
     * Register a lightweight member account (email + password only).
     */
    public function register(array $data): array
    {
        $localPart = explode('@', $data['email'])[0] ?? 'member';
        $baseUsername = Str::slug($localPart, '_') ?: 'member';
        $username = $baseUsername;
        $suffix = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $suffix++;
            $username = $baseUsername.'_'.$suffix;
        }

        $user = User::create([
            'name' => 'New Member',
            'username' => $username,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_type' => 'member',
            'status' => UserStatus::Active,
        ]);

        if (Role::query()->where('name', 'member')->exists()) {
            $user->assignRole('member');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    /**
     * Login or create account from social provider.
     */
    public function socialLogin(string $provider, SocialiteUser $socialUser): array
    {
        $providerId = (string) $socialUser->getId();
        $email = $socialUser->getEmail();

        if (! $email) {
            $email = sprintf('%s_%s@social.local', $provider, $providerId);
        }

        $user = User::query()->where('email', $email)->first();
        $requiresProfileCompletion = false;

        if (! $user) {
            $baseName = trim((string) ($socialUser->getName() ?: $socialUser->getNickname() ?: 'New Member'));
            $baseUsername = Str::slug(explode('@', $email)[0] ?: ($provider.'_'.$providerId), '_') ?: 'member';
            $username = $baseUsername;
            $suffix = 1;

            while (User::withTrashed()->where('username', $username)->exists()) {
                $suffix++;
                $username = $baseUsername.'_'.$suffix;
            }

            $user = User::create([
                'name' => $baseName,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role_type' => 'member',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);

            if (Role::query()->where('name', 'member')->exists()) {
                $user->assignRole('member');
            }

            $requiresProfileCompletion = true;
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
            'requires_profile_completion' => $requiresProfileCompletion,
        ];
    }

    /**
     * Attempt to authenticate a user and issue a Sanctum token.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): array
    {
        $field = $request->loginField(); // 'email' or 'phone'

        /** @var User|null $user */
        $user = User::where($field, $request->login)->first();

        // Use the same message for missing user and wrong password to prevent
        // user enumeration attacks.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        // Block soft-deleted users (withTrashed already excluded via query scope)
        // Block non-active statuses
        if (! $user->canLogin()) {
            $message = match ($user->status) {
                UserStatus::Pending   => 'Your account is awaiting approval.',
                UserStatus::Inactive  => 'Your account has been deactivated.',
                UserStatus::Suspended => 'Your account has been suspended. Contact support.',
                default               => 'Your account is not active.',
            };

            throw ValidationException::withMessages(['login' => [$message]]);
        }

        // Update login audit fields
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    /**
     * Send a password reset link to the given email.
     * Does NOT reveal whether the email exists in the system.
     */
    public function forgotPassword(string $email): void
    {
        // Laravel's broker sends the link only if the address is registered.
        // We intentionally ignore the status to prevent email enumeration.
        Password::broker()->sendResetLink(['email' => $email]);
    }

    /**
     * Reset the user's password using the broker token.
     *
     * @throws ValidationException
     */
    public function resetPassword(array $data): void
    {
        $status = Password::broker()->reset(
            [
                'email'                 => $data['email'],
                'password'              => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token'                 => $data['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all existing tokens after a password reset for security
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }
    }

    /**
     * Change an authenticated user's password.
     *
     * After change, all OTHER tokens are revoked and the user must re-login.
     * The current token is also revoked, so the client must re-authenticate.
     *
     * @throws ValidationException
     */
    public function changePassword(User $user, ChangePasswordRequest $request): void
    {
        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Revoke ALL tokens after password change — client must re-login.
        // This is the most secure behaviour; document it in API docs.
        $user->tokens()->delete();
    }

    /**
     * Revoke the currently authenticated token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Revoke every token the user has (logout from all devices).
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
