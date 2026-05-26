<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Registration::query()->latest('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', Rule::exists('courses', 'id')],
        ]);

        $registration = Registration::create($payload);

        return response()->json($registration, 201);
    }
}
