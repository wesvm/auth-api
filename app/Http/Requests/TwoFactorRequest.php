<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'ticket' => 'required|string|uuid',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/'
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => '2FA code must contain only numbers',
        ];
    }
}
