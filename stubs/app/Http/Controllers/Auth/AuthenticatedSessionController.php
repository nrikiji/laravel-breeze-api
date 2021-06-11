<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = User::whereEmail($request->email)->first();

        $user->tokens()->delete();
        $token = $user->createToken("login:user{$user->id}")->plainTextToken;

        return response()->json(['token' => $token], Response::HTTP_OK);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $request->user()->tokens()->delete();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json(null, 200);
        //return response()->setStatusCode()->json;
        //return response()->noContent();
    }
}
