<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->with('error', 'Email atau kata sandi tidak valid.');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email,' . $user->id . ',id'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user,
        ]);
    }

    public function updateProfilePhoto(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            // Delete old file if stored under /storage
            if (!empty($user->photo_url)) {
                $prefix = '/storage/';
                if (str_starts_with($user->photo_url, $prefix)) {
                    $relative = ltrim(substr($user->photo_url, strlen($prefix)), '/');
                    if ($relative && Storage::disk('public')->exists($relative)) {
                        Storage::disk('public')->delete($relative);
                    }
                }
            }

            $path = $request->file('photo')->store('users', 'public');
            $publicUrl = Storage::url($path); // /storage/users/...
            $user->photo_url = $publicUrl;
            $user->save();

            return response()->json([
                'message' => 'Foto profil berhasil diperbarui',
                'data' => $user,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to upload profile photo', [
                'user_id' => optional(Auth::user())->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Gagal upload foto',
                // Return as validation-like structure so UI can surface it under the photo field
                'errors' => [
                    'photo' => [config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan saat mengunggah foto.'],
                ],
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->password = $validated['new_password'];
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diubah',
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
