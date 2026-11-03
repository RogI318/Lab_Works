<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::orderBy('code')->get();

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        return view('courses.create');
    }

    public function store(Request $request)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $validated = $request->validate($this->rules(), $this->messages());
        Course::create($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course added successfully.');
    }

    public function edit(Course $course)
    {
        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $validated = $request->validate($this->rules($course->id), $this->messages());
        $course->update($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    private function rules($ignoreId = null): array
    {
        return [
            'code'       => 'required|string|max:20|unique:courses,code' . ($ignoreId ? ",$ignoreId" : ''),
            'name'       => 'required|string|max:255',
            'instructor' => 'required|string|max:255',
        ];
    }

    private function messages(): array
    {
        return [
            'code.required'       => 'Please provide a course code (e.g., WST101).',
            'code.unique'         => 'This course code already exists.',
            'name.required'       => 'Please provide the course name.',
            'instructor.required' => 'Please provide the instructor name.',
        ];
    }
}