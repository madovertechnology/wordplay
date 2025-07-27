<?php

namespace App\Http\Requests\WordScramble;

use Illuminate\Foundation\Http\FormRequest;

class GetLeaderboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all users to view leaderboards
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
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100', // Reasonable limit
            ],
            'date' => [
                'sometimes',
                'date_format:Y-m-d',
                'before_or_equal:today',
                'after:2024-01-01', // Reasonable date range
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
            'limit.integer' => 'The limit must be a valid number.',
            'limit.min' => 'The limit must be at least 1.',
            'limit.max' => 'The limit cannot exceed 100.',
            'date.date_format' => 'The date must be in YYYY-MM-DD format.',
            'date.before_or_equal' => 'The date cannot be in the future.',
            'date.after' => 'The date must be after January 1, 2024.',
        ];
    }
}