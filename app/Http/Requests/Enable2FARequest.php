<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Enable2FARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
