<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'start_time' => ['sometimes', 'required', 'date'],
            'end_time' => ['sometimes', 'required', 'date', 'after:start_time'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
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
            'title.required' => 'Please enter a title for the event.',
            'title.max' => 'Title must not exceed 255 characters.',
            'description.required' => 'Please enter a description for the event.',
            'start_time.required' => 'Please specify when the event starts.',
            'end_time.required' => 'Please specify when the event ends.',
            'end_time.after' => 'The event end time must be after the start time.',
            'max_participants.integer' => 'Maximum participants must be a number.',
            'max_participants.min' => 'Maximum participants must be at least 1.',
        ];
    }
}
