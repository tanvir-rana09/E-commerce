<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    //
    function register(AuthRequest $request)
    {
        $validatedData = $request->validated();
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['name']),
        ]);

        if ($user) {
            # code...
            return response()->json(["data" => $validatedData, "status" => "201", "message" => "User created successfully"]);
        }
    }

    function login(Request $request)
    {
        // $credentials = $request->only('email', 'password');

        // if (!$token = JWTAuth::attempt($credentials)) {
        //     return response()->json(['error' => 'Invalid credentials'], 401);
        // }

        // $user = JWTAuth::setToken($token)->authenticate();
        // $user->update(['token' => $token]);

        // return response()->json(['token' => $token, 'user' => $user], 200);


        $credentials = $request->only('email', 'password');
        $token = JWTAuth::attempt($credentials);
        if (!$token) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // $user = Auth::user();
        // $user->update(['token' => $token]);
        $user = JWTAuth::setToken($token)->authenticate();
        $user->update(['token' => $token]);

        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    function profile(){
        $user = Auth::user()->only(['id', 'name', 'email']);
        return response()->json([
            "status" => "success",
            "data" => $user
        ], 200);
    }

    function logout()
    {
        // jwt
        JWTAuth::invalidate(JWTAuth::parseToken());
        Auth::user()->update(['token' => null]);

        return response()->json(['message' => "log out successfully"], 200);
    }
}
