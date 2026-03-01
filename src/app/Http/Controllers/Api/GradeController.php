<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    /**
     * Store or update grades for an enrollment
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'quarter_1' => 'nullable|numeric|min:0|max:100',
            'quarter_2' => 'nullable|numeric|min:0|max:100',
            'quarter_3' => 'nullable|numeric|min:0|max:100',
            'quarter_4' => 'nullable|numeric|min:0|max:100',
        ]);

        $enrollment = Enrollment::with('grade')->findOrFail($id);

        $data = $request->only([
            'quarter_1',
            'quarter_2',
            'quarter_3',
            'quarter_4'
        ]);

        // Compute final grade (average of available quarters)
        $quarters = collect($data)->filter(fn($q) => !is_null($q));

        $final = $quarters->count() > 0
            ? round($quarters->avg(), 2)
            : null;

        $remarks = null;

        if (!is_null($final)) {
            $remarks = $final >= 75 ? 'Passed' : 'Failed';
        }

        $grade = $enrollment->grade()->updateOrCreate(
            ['enrollment_id' => $enrollment->id],
            [
                ...$data,
                'final_grade' => $final,
                'remarks' => $remarks
            ]
        );

        return response()->json([
            'message' => 'Grades saved successfully.',
            'data' => $grade
        ], 200);
    }

    /**
     * Show grade for an enrollment
     */
    public function show($id)
    {
        $enrollment = Enrollment::with(['student.user', 'subject', 'grade'])
            ->findOrFail($id);

        return response()->json($enrollment);
    }

    /**
     * View academic history grouped by school year and semester
     */
    public function history()
    {
        $student = auth()->user()->student;

        $enrollments = $student->enrollments()
            ->with(['subject', 'grade'])
            ->get()
            ->groupBy([
                'school_year',
                'semester'
            ]);

        return response()->json([
            'academic_history' => $enrollments
        ]);
    }

    /**
     * View GPA (Weighted by subject units)
     */
    public function gpa()
    {
        $student = auth()->user()->student;

        $enrollments = $student->enrollments()
            ->with(['subject', 'grade'])
            ->get()
            ->filter(fn($e) => $e->grade && !is_null($e->grade->final_grade));

        $totalWeighted = 0;
        $totalUnits = 0;

        foreach ($enrollments as $enrollment) {
            $units = $enrollment->subject->units;
            $grade = $enrollment->grade->final_grade;

            $totalWeighted += ($grade * $units);
            $totalUnits += $units;
        }

        $gpa = $totalUnits > 0
            ? round($totalWeighted / $totalUnits, 2)
            : 0;

        return response()->json([
            'gpa' => $gpa
        ]);
    }

    /**
     * View grades per subject (flat list)
     */
    public function grades()
    {
        $student = auth()->user()->student;

        $grades = $student->enrollments()
            ->with(['subject', 'grade'])
            ->get()
            ->map(function ($enrollment) {
                return [
                    'subject' => $enrollment->subject->name,
                    'units' => $enrollment->subject->units,
                    'school_year' => $enrollment->school_year,
                    'semester' => $enrollment->semester,
                    'quarter_1' => $enrollment->grade->quarter_1 ?? null,
                    'quarter_2' => $enrollment->grade->quarter_2 ?? null,
                    'quarter_3' => $enrollment->grade->quarter_3 ?? null,
                    'quarter_4' => $enrollment->grade->quarter_4 ?? null,
                    'final_grade' => $enrollment->grade->final_grade ?? null,
                    'remarks' => $enrollment->grade->remarks ?? null,
                ];
            });

        return response()->json([
            'grades' => $grades
        ]);
    }
}
