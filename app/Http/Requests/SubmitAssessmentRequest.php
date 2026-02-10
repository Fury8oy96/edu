<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssessmentRequest extends FormRequest
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
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:assessment_questions,id'],
            'answers.*.answer' => ['required'],
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
            'answers.required' => 'Answers are required',
            'answers.array' => 'Answers must be an array',
            'answers.*.question_id.required' => 'Question ID is required for each answer',
            'answers.*.question_id.exists' => 'One or more question IDs are invalid',
            'answers.*.answer.required' => 'Answer is required for each question',
        ];
    }
}
