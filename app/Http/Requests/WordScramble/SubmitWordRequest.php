<?php

namespace App\Http\Requests\WordScramble;

use Illuminate\Foundation\Http\FormRequest;

class SubmitWordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the user is authenticated
        if (auth()->check()) {
            // Additional check: ensure user account is active
            $user = auth()->user();
            if (method_exists($user, 'is_active') && !$user->is_active) {
                return false;
            }
            return true;
        }
        
        // For guests, we'll allow the request and let the controller handle guest token creation
        // This is more user-friendly than blocking the request
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
            'word' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z]+$/', // Only letters allowed
                'not_regex:/\s/', // No spaces allowed
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
            'word.required' => 'A word is required to submit.',
            'word.string' => 'The word must be a valid string.',
            'word.min' => 'The word must be at least 3 characters long.',
            'word.max' => 'The word cannot be longer than 20 characters.',
            'word.regex' => 'The word can only contain letters.',
            'word.not_regex' => 'The word cannot contain spaces.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim and convert to lowercase
        if ($this->has('word')) {
            $this->merge([
                'word' => strtolower(trim($this->word)),
            ]);
        }
    }
}