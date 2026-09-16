<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Clase utilizada para encriptar la contraseña

use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request){
        // Lógica para registrar un nuevo usuario
        $user = User::create($request->all());

        return response()->json($user, 201);
    }

    public function login(Request $request){
        // Lógica para autenticar un usuario
        $user = User::where('email', $request->email)->first();

        if(!$user){
            return response()->json(['response' => 'Usuario no encontrado'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Contraseña incorrecta'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'response' => 'Usuario autenticado correctamente'
        ]);
    }

    public function logout(Request $request){
        // Lógica para cerrar sesión de un usuario
        $request->user()->currentAccessToken()->delete();

        return response()->json(['response' => 'Sesión cerrada correctamente']);
    }
}
