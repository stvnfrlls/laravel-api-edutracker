<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // CREATE schedule
    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'day' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string',
            'instructor_id' => 'nullable|exists:users,id'
        ]);

        $schedule = Schedule::create($request->all());

        return response()->json($schedule, 201);
    }

    // READ: get schedule of a subject
    public function show($subjectId)
    {
        $schedules = Schedule::where('subject_id', $subjectId)->get();

        return response()->json($schedules);
    }

    // UPDATE schedule
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'day' => 'sometimes|string',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'room' => 'nullable|string',
            'instructor_id' => 'nullable|exists:users,id'
        ]);

        $schedule->update($request->all());

        return response()->json($schedule);
    }

    // DELETE schedule
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json(['message' => 'Schedule deleted']);
    }

    // My Schedule
    public function mySchedule(Request $request)
    {
        $user = $request->user();

        // Load student with subjects and their schedules
        $student = $user->student()->with('subjects.schedules')->first();

        if (!$student) {
            return response()->json(['message' => 'Student profile not found'], 404);
        }

        // Transform response
        $data = $student->subjects->map(function ($subject) {
            return [
                'subject_code' => $subject->code,
                'subject_name' => $subject->name,
                'schedules' => $subject->schedules->map(function ($schedule) {
                    return [
                        'day' => $schedule->day,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'room' => $schedule->room,
                        'instructor_id' => $schedule->instructor_id,
                    ];
                }),
            ];
        });

        return response()->json($data);
    }
}
