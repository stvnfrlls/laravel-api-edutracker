<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentController extends Controller
{
    /**
     * CREATE - Assign student to subject
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:students,user_id',
            'subject_id' => 'required|exists:subjects,id',
            'school_year' => 'required|string',
            'semester' => 'required|string',
        ]);

        $student = Student::where('user_id', $request->user_id)->firstOrFail();

        // Check full unique combination — same subject different semester is allowed
        if (
            $student->subjects()
                ->wherePivot('school_year', $request->school_year)
                ->wherePivot('semester', $request->semester)
                ->where('subject_id', $request->subject_id)
                ->exists()
        ) {
            return response()->json([
                'message' => 'Student already enrolled in this subject'
            ], 422);
        }

        $student->subjects()->attach($request->subject_id, [
            'school_year' => $request->school_year,
            'semester' => $request->semester,
        ]);

        return response()->json([
            'message' => 'Student successfully enrolled',
            'student_number' => $student->student_number,
            'subject_id' => $request->subject_id
        ], 201);
    }

    /**
     * READ - View subjects of a student
     */
    public function show($user_id)
    {
        $student = Student::where('user_id', $user_id)->firstOrFail();

        return response()->json([
            'student_id' => $student->id,
            'subjects' => $student->subjects
        ]);
    }

    /**
     * DELETE - Remove student from subject
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:students,user_id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $student = Student::where('user_id', $request->user_id)->firstOrFail();

        $student->subjects()->detach($request->subject_id);

        return response()->json([
            'message' => 'Student unenrolled successfully'
        ]);
    }

    /**
     * UPDATE
     * If you later add fields like grade/status in pivot
     */
    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:students,user_id',
            'subject_id' => 'required|exists:subjects,id',
            'status' => 'required|string'
        ]);

        $student = Student::where('user_id', $request->user_id)->firstOrFail();

        $student->subjects()->updateExistingPivot(
            $request->subject_id,
            ['status' => $request->status]
        );

        return response()->json([
            'message' => 'Enrollment updated'
        ]);
    }
}
