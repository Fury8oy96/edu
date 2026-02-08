<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'search' => ['string', 'max:255'],
            'instructor' => ['integer', 'exists:instructors,id'],
            'category' => ['string', 'max:100'],
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
            'page.integer' => 'The page must be an integer.',
            'page.min' => 'The page must be at least 1.',
            'per_page.integer' => 'The per page value must be an integer.',
            'per_page.min' => 'The per page value must be at least 1.',
            'per_page.max' => 'The per page value must not exceed 100.',
            'search.string' => 'The search query must be a string.',
            'search.max' => 'The search query must not exceed 255 characters.',
            'instructor.integer' => 'The instructor ID must be an integer.',
            'instructor.exists' => 'The selected instructor does not exist.',
            'category.string' => 'The category must be a string.',
            'category.max' => 'The category must not exceed 100 characters.',
        ];
    }
}
