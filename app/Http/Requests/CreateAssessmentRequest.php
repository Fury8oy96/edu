<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit' => ['required', 'integer', 'min:1'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'required_with:start_date', 'after:start_date'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'The selected course does not exist',
            'title.required' => 'Assessment title is required',
            'time_limit.required' => 'Time limit is required',
            'time_limit.min' => 'Time limit must be at least 1 minute',
            'passing_score.required' => 'Passing score is required',
            'passing_score.min' => 'Passing score must be at least 0',
            'passing_score.max' => 'Passing score cannot exceed 100',
            'max_attempts.min' => 'Maximum attempts must be at least 1',
            'start_date.required_with' => 'Start date is required when end date is provided',
            'end_date.required_with' => 'End date is required when start date is provided',
            'end_date.after' => 'End date must be after start date',
        ];
    }
}
