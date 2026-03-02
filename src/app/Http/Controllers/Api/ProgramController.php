<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:programs,name'
        ]);

        $program = Program::create($validated);

        return response()->json($program, 201);
    }

    public function attachSubject(Request $request, Program $program)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'year_level' => 'required|integer|min:1|max:5',
            'semester' => 'required|string'
        ]);

        if (
            $program->subjects()
                ->where('subject_id', $validated['subject_id'])
                ->exists()
        ) {

            return response()->json([
                'message' => 'Subject already exists in curriculum'
            ], 422);
        }

        $program->subjects()->attach(
            $validated['subject_id'],
            [
                'year_level' => $validated['year_level'],
                'semester' => $validated['semester']
            ]
        );

        return response()->json(['message' => 'Subject attached'], 201);
    }
}
