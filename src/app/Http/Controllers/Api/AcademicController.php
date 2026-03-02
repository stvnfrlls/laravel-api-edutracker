<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Semester;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function storeSchoolYear(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:school_years,name',
            'is_active' => 'sometimes|boolean'
        ]);

        if (isset($validated['is_active']) && $validated['is_active']) {
            SchoolYear::query()->update(['is_active' => false]);
        }

        $schoolYear = SchoolYear::create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return response()->json($schoolYear, 201);
    }

    public function storeSemester(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'name' => 'required'
        ]);

        return Semester::create($validated);
    }
}
