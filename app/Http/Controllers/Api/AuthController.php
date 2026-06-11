<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Models\WorkerRegistrationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'resident',
            'organization_id' => null,
        ]);

        return response()->json([
            'message' => 'Регистрация выполнена успешно',
            'user' => $user,
            'token' => $this->createPlainToken($user, 'android-token'),
        ], 201);
    }


    public function registerWorker(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'registration_code' => ['required', 'string', 'max:32'],
        ]);

        $normalizedCode = strtoupper(trim($data['registration_code']));

        $user = DB::transaction(function () use ($data, $normalizedCode) {
            $registrationCode = WorkerRegistrationCode::query()
                ->where('code', $normalizedCode)
                ->lockForUpdate()
                ->first();

            if (!$registrationCode) {
                throw ValidationException::withMessages([
                    'registration_code' => ['Регистрационный код не найден'],
                ]);
            }

            if (!$registrationCode->isAvailable()) {
                throw ValidationException::withMessages([
                    'registration_code' => ['Регистрационный код уже использован, отключён или просрочен'],
                ]);
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'worker',
                'organization_id' => $registrationCode->organization_id,
            ]);

            $registrationCode->used_count = $registrationCode->used_count + 1;
            $registrationCode->used_by_user_id = $user->id;
            $registrationCode->used_at = now();

            if ($registrationCode->used_count >= $registrationCode->max_uses) {
                $registrationCode->active = false;
            }

            $registrationCode->save();

            return $user->load('organization');
        });

        return response()->json([
            'message' => 'Регистрация сотрудника ЖКХ выполнена успешно',
            'user' => $user,
            'token' => $this->createPlainToken($user, 'android-token'),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль'],
            ]);
        }

        if ($user->banned_at) {
            throw ValidationException::withMessages([
                'email' => ['Аккаунт заблокирован'],
            ]);
        }

        return response()->json([
            'message' => 'Вход выполнен успешно',
            'user' => $user,
            'token' => $this->createPlainToken($user, 'android-token'),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('api_token');

        if ($token instanceof ApiToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Выход выполнен успешно',
        ]);
    }

    private function createPlainToken(User $user, string $name): string
    {
        $plainToken = Str::random(80);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return $plainToken;
    }
}
