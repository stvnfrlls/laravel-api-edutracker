<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $student = $user->student()->with('subjects')->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found'
            ], 404);
        }

        return response()->json([
            'student_number' => $student->student_number,
            'subjects' => $student->subjects
        ]);
    }
}
