<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Disable2FARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required_without:code|string',
            'code' => 'required_without:password|string|size:6|regex:/^[0-9]{6}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required_without' => 'Password or 2FA code is required',
            'code.required_without' => 'Password or 2FA code is required',
            'code.regex' => '2FA code must contain only numbers',
        ];
    }
}
