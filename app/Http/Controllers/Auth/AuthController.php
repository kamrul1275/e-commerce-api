<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{


// Register new user
public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required_without:phone|nullable|email|unique:users,email',
        'phone' => 'required_without:email|nullable|regex:/^01[0-9]{9}$/|unique:users,phone',
        'password' => 'required|min:6',
    ], [
        'name.required' => 'Name field is required.',
        'email.unique' => 'This email is already registered.',
        'phone.unique' => 'This phone number is already registered.',
        'email.required_without' => 'Email or Phone is required.',
        'phone.required_without' => 'Phone or Email is required.',
        'phone.regex' => 'Phone number must be valid (e.g. 01XXXXXXXXX).',
    ]);

    // Create user with default role = 'customer'
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'password' => Hash::make($request->password),
        'role' => 'customer', // 👈 default role
    ]);

    return response()->json([
        'message' => 'User registered successfully',
        'user' => $user
    ], 201);
}


    // Login via Email or Phone
public function login(Request $request)
{

    //  dd($request->all());
    // Validate input
    $request->validate([
        'login' => 'required',
        'password' => 'required|min:6',
    ]);


   
    // Detect login field
    $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

    // Credentials
    $credentials = [
        $loginField => $request->input('login'),
        'password' => $request->input('password')
    ];

    // Attempt login
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    // Invalid credentials
    return response()->json(['message' => 'Invalid credentials'], 401);
}


    // Generate OTP for login
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required']);
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['message' => 'Phone not found'], 404);
        }

        $otp = rand(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Here you would send SMS (Integration pending)
        return response()->json(['message' => 'OTP sent successfully', 'otp' => $otp]); // for testing only
    }

    // Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'otp' => 'required'
        ]);

        $user = User::where('phone', $request->phone)
                    ->where('otp_code', $request->otp)
                    ->where('otp_expires_at', '>', Carbon::now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $user->update(['otp_code' => null]);

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully',
            'token' => $token,
            'user' => $user
        ]);
    }

    // Logout user
    // public function logout(Request $request)
    // {
    //     $request->user()->tokens()->delete();
    //     return response()->json(['message' => 'Logged out successfully']);
    // }


    public function logout(Request $request)
{
    // Only works when user is authenticated via Sanctum
    $user = $request->user();

    if ($user) {
        $user->tokens()->delete(); // delete all tokens
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    return response()->json(['message' => 'No authenticated user found'], 401);
}

}
