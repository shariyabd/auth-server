<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\TokenRepository;

class SsoController extends Controller
{

    public function sessionCheck(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json([
                'authenticated' => true,
                'user' => [
                    'id' => Auth::user()->id,
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
            ]);
        }

        return response()->json([
            'authenticated' => false,
        ], 401);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return redirect()->back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.login');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('name', 'email'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.register');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $this->revokeAllTokens($user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirectUri = $request->query('redirect_uri');

        if ($redirectUri && $this->isAllowedRedirectUri($redirectUri)) {
            return redirect($redirectUri);
        }

        return redirect('/login');
    }

    public function apiLogout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {

            $token = $user->token();
            $token->revoke();

            $refreshTokenRepository = app(RefreshTokenRepository::class);
            $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token->id);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    private function revokeAllTokens(User $user): void
    {
        $tokenRepository = app(TokenRepository::class);
        $refreshTokenRepository = app(RefreshTokenRepository::class);

        $tokens = DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->get();

        foreach ($tokens as $token) {
            $tokenRepository->revokeAccessToken($token->id);
            $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token->id);
        }
    }

    private function isAllowedRedirectUri(string $uri): bool
    {
        $allowedOrigins = [
            config('services.sso.ecommerce_url'),
            config('services.sso.foodpanda_url'),
        ];

        foreach ($allowedOrigins as $origin) {
            if (str_starts_with($uri, $origin)) {
                return true;
            }
        }

        return false;
    }

    public function dashboard()
    {
        $user = Auth::user();

        $activeTokens = DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->whereNull('expires_at')
            ->orWhere(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('revoked', false)
                    ->where('expires_at', '>', now());
            })
            ->count();

        return view('auth.dashboard', [
            'user' => $user,
            'activeTokens' => $activeTokens,
        ]);
    }
}