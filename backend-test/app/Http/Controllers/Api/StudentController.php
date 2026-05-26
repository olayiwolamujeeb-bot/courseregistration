<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Student::query()->latest('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $student = Student::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'matric' => ['nullable', 'string', 'max:100'],
        ]));

        return response()->json($student, 201);
    }
}
