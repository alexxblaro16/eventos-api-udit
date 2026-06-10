<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registro de un usuario nuevo. Devuelve el usuario y su token de acceso.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6'],
        ]);

        /** @var User $user */
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // createToken: nombre del token, abilities (permisos) y caducidad
        $token = $user->createToken('api-token', ['full_access'], now()->addWeek())->plainTextToken;

        return $this->jsonOk([
            'usuario' => new UserResource($user),
            'token' => $token,
        ], 'Usuario registrado correctamente.', 201);
    }

    /**
     * Inicio de sesión. Borra tokens previos y crea uno nuevo.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        /** @var User $user */
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->jsonError('Credenciales inválidas.', null, 401);
        }

        // Un token activo por usuario: borramos los anteriores
        $user->tokens()->delete();
        $token = $user->createToken('api-token', ['full_access'], now()->addWeek())->plainTextToken;

        return $this->jsonOk([
            'usuario' => new UserResource($user),
            'token' => $token,
        ], 'Sesión iniciada.');
    }

    /**
     * Cierre de sesión. Borra todos los tokens del usuario autenticado.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->jsonOk(null, 'Sesión cerrada.');
    }

    /**
     * Perfil del usuario autenticado.
     */
    public function me(Request $request)
    {
        return $this->jsonOk(new UserResource($request->user()), 'Perfil del usuario.');
    }

    /**
     * Activa el rol de organizador para el usuario autenticado (vía Service).
     */
    public function convertirseEnOrganizador(Request $request)
    {
        $service = app()->get(UserService::class);
        $user = $service->activarOrganizador($request->user());

        return $this->jsonOk(new UserResource($user), 'Ya eres organizador: puedes crear eventos.');
    }
}
