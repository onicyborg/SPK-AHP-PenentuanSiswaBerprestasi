<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class AssessorController extends Controller
{
    /**
     * List users page
     */
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('manage-assessor', compact('users'));
    }

    /**
     * Show single user
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json(['data' => $user]);
    }

    /**
     * Create user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:150'],
            'email' => ['required','email','max:191','unique:users,email'],
            'password' => ['required','string','min:8'],
            'photo' => ['nullable','image','mimes:jpeg,png,jpg,webp','max:2048'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
        $payload = $validator->validated();

        $user = new User();
        $user->name = $payload['name'];
        $user->email = $payload['email'];
        $user->password = $payload['password'];
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('users', 'public');
            $user->photo_url = Storage::url($path);
        }
        $user->save();

        return response()->json(['message' => 'Pengguna berhasil dibuat', 'data' => $user]);
    }

    /**
     * Update user
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:150'],
            'email' => [
                'required','email','max:191',
                Rule::unique('users', 'email')->ignore($user->id, 'id')
            ],
            'password' => ['nullable','string','min:8'],
            'photo' => ['nullable','image','mimes:jpeg,png,jpg,webp','max:2048'],
            'photo_remove' => ['nullable','boolean'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
        $payload = $validator->validated();

        $user->name = $payload['name'];
        $user->email = $payload['email'];
        if (!empty($payload['password'] ?? null)) {
            $user->password = $payload['password'];
        }

        if ($request->boolean('photo_remove')) {
            if (!empty($user->photo_url) && str_starts_with($user->photo_url, '/storage/')) {
                $relative = ltrim(substr($user->photo_url, strlen('/storage/')), '/');
                try { Storage::disk('public')->delete($relative); } catch (\Throwable $e) {}
            }
            $user->photo_url = null;
        }
        if ($request->hasFile('photo')) {
            if (!empty($user->photo_url) && str_starts_with($user->photo_url, '/storage/')) {
                $relative = ltrim(substr($user->photo_url, strlen('/storage/')), '/');
                try { Storage::disk('public')->delete($relative); } catch (\Throwable $e) {}
            }
            $path = $request->file('photo')->store('users', 'public');
            $user->photo_url = Storage::url($path);
        }

        $user->save();

        return response()->json(['message' => 'Pengguna berhasil diperbarui', 'data' => $user]);
    }

    /**
     * Delete user
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        if (Auth::id() === $user->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun yang sedang digunakan'], 422);
        }
        if (!empty($user->photo_url) && str_starts_with($user->photo_url, '/storage/')) {
            $relative = ltrim(substr($user->photo_url, strlen('/storage/')), '/');
            try { Storage::disk('public')->delete($relative); } catch (\Throwable $e) {}
        }
        $user->delete();
        return response()->json(['message' => 'Pengguna berhasil dihapus']);
    }
}
