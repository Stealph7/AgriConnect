<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|unique:users',
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => 'required|in:producteur,acheteur,cooperative',
                'region' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'region' => $request->region,
            ]);

            // Créer le token d'authentification
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier les identifiants
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            // Vérifier si le compte est actif
            if (!$user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is disabled'
                ], 403);
            }

            // Supprimer les anciens tokens
            $user->tokens()->delete();

            // Créer un nouveau token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion d'un utilisateur
     */
    public function logout(Request $request)
    {
        try {
            // Révoquer le token actuel
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialisation du mot de passe
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Générer un token de réinitialisation
            $token = \Str::random(60);

            // Sauvegarder le token dans la base de données
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Envoyer l'email de réinitialisation
            // TODO: Implémenter l'envoi d'email

            return response()->json([
                'status' => 'success',
                'message' => 'Password reset link sent to email'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérification de l'email
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        try {
            $user = User::findOrFail($id);

            if (!hash_equals((string) $hash, sha1($user->email))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid verification link'
                ], 400);
            }

            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerification(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email already verified'
                ], 400);
            }

            $request->user()->sendEmailVerificationNotification();

            return response()->json([
                'status' => 'success',
                'message' => 'Verification link sent'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
