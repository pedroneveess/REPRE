<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{

    public function createUser(AuthRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'birthdate' => $validated['birthdate'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario criado com sucesso ',
            'user' => new UserResource($user),
            'token' => $token
        ], 201);
    }

    public function userLogin(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais invalidas.'], 401);
        };

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        Password::sendResetLink($request->validated());
        return response()->json(['message' => 'Se o email existir, enviaremos instrucoes de recuperacao.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset($request->validated(), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $user->tokens()->delete();
        });
        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Nao foi possivel redefinir a senha.'], 422);
        }
        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }

    public function logoutUser(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    public function updateUser(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Dados atualizados com sucesso.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function deleteUser(Request $request)
    {
        $user = $request->user();

        try {
            DB::transaction(function () use ($user) {
                $user->tokens()->delete();
                $user->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23503') {
                return response()->json(['message' => 'Conta possui registros vinculados.'], 409);
            }
            throw $exception;
        }

        return response()->json(null, 204);
    }

    public function loggedUser(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
