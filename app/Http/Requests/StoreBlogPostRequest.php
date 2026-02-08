<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'content' => ['required', 'string', 'min:10', 'max:50000'],
            'status' => ['sometimes', 'in:draft,published'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'min:2', 'max:30'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
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
            'title.required' => 'Please enter a title for your blog post.',
            'title.min' => 'Title must be at least 3 characters.',
            'title.max' => 'Title must not exceed 200 characters.',
            'content.required' => 'Please enter content for your blog post.',
            'content.min' => 'Content must be at least 10 characters.',
            'content.max' => 'Content must not exceed 50000 characters.',
            'status.in' => 'Status must be either draft or published.',
            'category_id.exists' => 'The selected category does not exist.',
            'tags.array' => 'Tags must be provided as an array.',
            'tags.*.string' => 'Each tag must be a string.',
            'tags.*.min' => 'Each tag must be at least 2 characters.',
            'tags.*.max' => 'Each tag must not exceed 30 characters.',
            'featured_image.image' => 'The featured image must be an image file.',
            'featured_image.mimes' => 'The featured image must be a file of type: jpeg, png, jpg, gif, webp.',
            'featured_image.max' => 'The featured image may not be greater than 5120 kilobytes.',
        ];
    }
}
