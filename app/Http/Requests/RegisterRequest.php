<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|lowercase|max:255|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|string|email|unique:users,email|max:255',
            'password' => 'required|string|confirmed|min:3',
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'The username field can only contain letters, numbers and underscores.',
        ];
    }
}
