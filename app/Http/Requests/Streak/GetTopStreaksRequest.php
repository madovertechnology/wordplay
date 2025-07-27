<?php

namespace App\Http\Requests\Streak;

use Illuminate\Foundation\Http\FormRequest;

class GetTopStreaksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can access streak information
        return auth()->check();
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
                'nullable',
                'integer',
                'min:1',
                'max:50',
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
            'limit.integer' => 'The limit must be a valid integer.',
            'limit.min' => 'The limit must be at least 1.',
            'limit.max' => 'The limit cannot exceed 50.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default value if not provided
        if (!$this->has('limit')) {
            $this->merge([
                'limit' => 10,
            ]);
        }
    }
}