<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateQuestionRequest extends FormRequest
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
        $rules = [
            'assessment_id' => ['required', 'integer', 'exists:assessments,id'],
            'question_type' => ['required', Rule::in(['multiple_choice', 'true_false', 'short_answer', 'essay'])],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'numeric', 'min:0.01'],
            'order' => ['sometimes', 'integer', 'min:1'],
        ];

        // Conditional validation based on question type
        if ($this->input('question_type') === 'multiple_choice') {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*.text'] = ['required', 'string'];
            $rules['options.*.is_correct'] = ['required', 'boolean'];
            $rules['correct_answer'] = ['required'];
        } elseif ($this->input('question_type') === 'true_false') {
            $rules['correct_answer'] = ['required', 'boolean'];
        } elseif (in_array($this->input('question_type'), ['short_answer', 'essay'])) {
            $rules['grading_rubric'] = ['nullable', 'string'];
            $rules['correct_answer'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assessment_id.required' => 'Assessment ID is required',
            'assessment_id.exists' => 'The selected assessment does not exist',
            'question_type.required' => 'Question type is required',
            'question_type.in' => 'Question type must be one of: multiple_choice, true_false, short_answer, essay',
            'question_text.required' => 'Question text is required',
            'points.required' => 'Points are required',
            'points.min' => 'Points must be greater than 0',
            'options.required' => 'Options are required for multiple choice questions',
            'options.min' => 'Multiple choice questions must have at least 2 options',
            'options.*.text.required' => 'Each option must have text',
            'options.*.is_correct.required' => 'Each option must specify if it is correct',
            'correct_answer.required' => 'Correct answer is required',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // For multiple choice, ensure exactly one option is marked as correct
            if ($this->input('question_type') === 'multiple_choice' && $this->has('options')) {
                $correctCount = collect($this->input('options'))->where('is_correct', true)->count();
                if ($correctCount !== 1) {
                    $validator->errors()->add('options', 'Exactly one option must be marked as correct');
                }
            }
        });
    }
}
