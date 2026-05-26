<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Course::query()->latest('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $course = Course::create($this->validatedPayload($request));

        return response()->json($course, 201);
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $course->update($this->validatedPayload($request));

        return response()->json($course->fresh());
    }

    public function destroy(Course $course): JsonResponse
    {
        $courseId = $course->id;
        $course->delete();

        Registration::query()->each(function (Registration $registration) use ($courseId): void {
            $filteredCourseIds = array_values(
                array_filter(
                    $registration->course_ids,
                    fn (int $registeredCourseId): bool => $registeredCourseId !== $courseId
                )
            );

            if ($filteredCourseIds !== $registration->course_ids) {
                $registration->update(['course_ids' => $filteredCourseIds]);
            }
        });

        return response()->json(null, 204);
    }

    private function validatedPayload(Request $request): array
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses', 'code')->ignore($request->route('course')),
            ],
            'level' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'max:50'],
        ]);

        $payload['code'] = strtoupper($payload['code']);

        return $payload;
    }
}
