<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::query()
                ->orderBy('id')
                ->limit(10)
                ->get(['id', 'name', 'email', 'created_at']);

            return response()->json([
                'success' => true,
                'message' => 'Usuarios obtenidos correctamente.',
                'data' => $users,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible obtener los usuarios.',
                'data' => null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos proporcionados no son válidos.',
                    'data' => $validator->errors(),
                ], 422);
            }

            $user = User::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente.',
                'data' => $user->only(['id', 'name', 'email', 'created_at']),
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible crear el usuario.',
                'data' => null,
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos proporcionados no son válidos.',
                    'data' => $validator->errors(),
                ], 422);
            }

            $credentials = $validator->validated();
            $user = User::where('email', $credentials['email'])->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las credenciales no son válidas.',
                    'data' => null,
                ], 401);
            }

            $plainTextToken = Str::random(64);
            $token = Token::create([
                'user_id' => $user->id,
                'token' => hash('sha256', $plainTextToken),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada correctamente.',
                'data' => [
                    'token' => $plainTextToken,
                    'token_type' => 'Bearer',
                    'user' => $user->only(['id', 'name', 'email']),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible iniciar sesión.',
                'data' => null,
            ], 500);
        }
    }

    public function updateName(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos proporcionados no son válidos.',
                    'data' => $validator->errors(),
                ], 422);
            }

            $user = $request->attributes->get('api_user');
            $user->update(['name' => $validator->validated()['name']]);

            return response()->json([
                'success' => true,
                'message' => 'Nombre actualizado correctamente.',
                'data' => $user->only(['id', 'name', 'email']),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible actualizar el nombre.',
                'data' => null,
            ], 500);
        }
    }
}
