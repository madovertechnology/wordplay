<?php

namespace App\Http\Requests\Leaderboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetUserRankRequest extends FormRequest
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
            'period' => [
                'nullable',
                'string',
                Rule::in(['daily', 'weekly', 'monthly', 'all-time']),
            ],
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
            'yearMonth' => [
                'nullable',
                'date_format:Y-m',
                'before_or_equal:' . now()->format('Y-m'),
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
            'period.in' => 'The period must be one of: daily, weekly, monthly, all-time.',
            'date.date_format' => 'The date must be in the format YYYY-MM-DD.',
            'date.before_or_equal' => 'The date cannot be in the future.',
            'yearMonth.date_format' => 'The year-month must be in the format YYYY-MM.',
            'yearMonth.before_or_equal' => 'The year-month cannot be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'period' => $this->period ?? 'all-time',
            'date' => $this->date ?? now()->toDateString(),
            'yearMonth' => $this->yearMonth ?? now()->format('Y-m'),
        ]);
    }
}