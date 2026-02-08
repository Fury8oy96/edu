<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Returns true for authenticated students.
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
            'content' => ['required', 'string', 'min:1', 'max:1000'],
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
            'content.required' => 'Please enter content for your comment.',
            'content.string' => 'Comment content must be a string.',
            'content.min' => 'Comment must be at least 1 character.',
            'content.max' => 'Comment must not exceed 1000 characters.',
        ];
    }
}
