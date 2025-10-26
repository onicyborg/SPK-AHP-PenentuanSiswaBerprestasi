<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'nis' => ['required','string','max:50','unique:students,nis'],
            'name' => ['required','string','max:150'],
            'class' => ['nullable','string','max:50'],
        ]);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $student = Students::create($data);
        return response()->json(['message' => 'Siswa ditambahkan','student' => $student]);
    }
}
