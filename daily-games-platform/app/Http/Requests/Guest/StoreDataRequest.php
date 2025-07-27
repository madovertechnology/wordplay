<?php

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataRequest extends FormRequest
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
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_\-\.]+$/', // Only alphanumeric, underscore, dash, and dot
            ],
            'value' => [
                'required',
                'max:10000', // Limit data size to prevent abuse
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => 'A key is required to store data.',
            'key.string' => 'The key must be a valid string.',
            'key.max' => 'The key cannot be longer than 255 characters.',
            'key.regex' => 'The key can only contain letters, numbers, underscores, dashes, and dots.',
            'value.required' => 'A value is required to store data.',
            'value.max' => 'The value cannot exceed 10,000 characters.',
        ];
    }
}