<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
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
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit_minutes' => 'required|integer|min:1|max:180',
            'passing_score_percentage' => 'required|integer|min:0|max:100',
            'max_attempts' => 'required|integer|min:1|max:10',
            'randomize_questions' => 'boolean',
        ];
    }
}
