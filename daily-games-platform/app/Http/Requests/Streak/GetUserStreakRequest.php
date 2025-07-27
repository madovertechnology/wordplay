<?php

namespace App\Http\Requests\Streak;

use Illuminate\Foundation\Http\FormRequest;

class GetUserStreakRequest extends FormRequest
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
            'game' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9\-]+$/', // Only lowercase letters, numbers, and dashes
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
            'game.required' => 'A game identifier is required.',
            'game.string' => 'The game identifier must be a valid string.',
            'game.max' => 'The game identifier cannot be longer than 50 characters.',
            'game.regex' => 'The game identifier can only contain lowercase letters, numbers, and dashes.',
        ];
    }
}