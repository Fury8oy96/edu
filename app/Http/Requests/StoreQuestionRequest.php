<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
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
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,short_answer',
            'points' => 'required|integer|min:1',
            'order' => 'required|integer|min:1',
            'options' => 'required_if:question_type,multiple_choice|array|min:2',
            'options.*.text' => 'required_with:options|string',
            'options.*.is_correct' => 'required_with:options|boolean',
            'correct_answer' => 'required_if:question_type,true_false|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->question_type === 'multiple_choice' && is_array($this->options)) {
                $correctCount = collect($this->options)
                    ->where('is_correct', true)
                    ->count();

                if ($correctCount !== 1) {
                    $validator->errors()->add(
                        'options',
                        'Exactly one option must be marked as correct.'
                    );
                }
            }
        });
    }
}
