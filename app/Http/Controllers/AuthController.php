<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['cin', 'password']);
        if (! $token = auth()->attempt([
            'cin'   => $credentials['cin'],
            'password'=> $credentials['password']
        ])) {
            return response()->json(['error' => 'CIN ou Mot de passe erroné.'], 401);
        }
        $newLogin = new \App\Login;
        $newLogin->type = "c";
        $newLogin->user_id = Auth::user()->id;
        $newLogin->save();
        return [
            "token" => $this->respondWithToken($token),
            "user" => Auth::user()
        ];
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {

        $newLogin = new \App\Login;
        $newLogin->type = "d";
        $newLogin->user_id = Auth::user()->id;
        $newLogin->save();
        auth()->logout();

        
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
