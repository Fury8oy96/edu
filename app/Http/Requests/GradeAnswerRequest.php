<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeAnswerRequest extends FormRequest
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
            'points_earned' => ['required', 'numeric', 'min:0'],
            'grader_feedback' => ['nullable', 'string'],
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
            'points_earned.required' => 'Points earned is required',
            'points_earned.numeric' => 'Points earned must be a number',
            'points_earned.min' => 'Points earned cannot be negative',
        ];
    }
}
