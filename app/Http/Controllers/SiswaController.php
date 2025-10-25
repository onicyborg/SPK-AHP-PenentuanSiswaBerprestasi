<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Students;

class SiswaController extends Controller
{
    /**
     * List students page
     */
    public function index()
    {
        $students = Students::orderBy('name')->get();
        return view('manage-siswa', compact('students'));
    }

    /**
     * Show single student
     */
    public function show(string $id)
    {
        $student = Students::findOrFail($id);
        return response()->json(['data' => $student]);
    }

    /**
     * Create student
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nis' => 'required|string|max:64|unique:students,nis',
            'name' => 'required|string|max:150',
            'class' => 'nullable|string|max:50',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
        $payload = $validator->validated();

        $student = new Students();
        $student->nis = $payload['nis'];
        $student->name = $payload['name'];
        $student->class = $payload['class'] ?? null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('public/students');
            $student->photo_url = Storage::url($path);
        }
        $student->save();

        return response()->json(['message' => 'Siswa berhasil dibuat', 'data' => $student]);
    }

    /**
     * Update student
     */
    public function update(Request $request, string $id)
    {
        $student = Students::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'nis' => [
                'required', 'string', 'max:64',
                Rule::unique('students', 'nis')->ignore($student->id, 'id')
            ],
            'name' => 'required|string|max:150',
            'class' => 'nullable|string|max:50',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'photo_remove' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
        $payload = $validator->validated();

        $student->nis = $payload['nis'];
        $student->name = $payload['name'];
        $student->class = $payload['class'] ?? null;

        if ($request->boolean('photo_remove')) {
            if (!empty($student->photo_url) && str_starts_with($student->photo_url, '/storage/')) {
                $storagePath = 'public/' . ltrim(substr($student->photo_url, strlen('/storage/')), '/');
                try { Storage::delete($storagePath); } catch (\Throwable $e) {}
            }
            $student->photo_url = null;
        }

        if ($request->hasFile('photo')) {
            if (!empty($student->photo_url) && str_starts_with($student->photo_url, '/storage/')) {
                $storagePath = 'public/' . ltrim(substr($student->photo_url, strlen('/storage/')), '/');
                try { Storage::delete($storagePath); } catch (\Throwable $e) {}
            }
            $path = $request->file('photo')->store('public/students');
            $student->photo_url = Storage::url($path);
        }

        $student->save();

        return response()->json(['message' => 'Siswa berhasil diperbarui', 'data' => $student]);
    }

    /**
     * Delete student
     */
    public function destroy(string $id)
    {
        $student = Students::findOrFail($id);

        // Cek relasi: jika masih memiliki relasi (mis. candidates), kembalikan error
        if (method_exists($student, 'candidates') && $student->candidates()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus. Data siswa sudah terhubung dengan data kandidat.',
            ], 422);
        }

        try {
            if (!empty($student->photo_url) && str_starts_with($student->photo_url, '/storage/')) {
                $storagePath = 'public/' . ltrim(substr($student->photo_url, strlen('/storage/')), '/');
                try { Storage::delete($storagePath); } catch (\Throwable $e) {}
            }
            $student->delete();
        } catch (\Illuminate\Database\QueryException $qe) {
            return response()->json([
                'message' => 'Tidak dapat menghapus karena sudah terhubung dengan data lain.',
                'error' => config('app.debug') ? $qe->getMessage() : null,
            ], 409);
        }

        return response()->json(['message' => 'Siswa berhasil dihapus']);
    }
}
