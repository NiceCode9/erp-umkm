<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'owner_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_email.unique' => 'Email Owner sudah terdaftar di sistem.',
            'owner_email.required' => 'Email Owner wajib diisi.',
            'owner_password.required' => 'Password Owner wajib diisi.',
            'owner_password.min' => 'Password Owner minimal 8 karakter.',
            'owner_password.confirmed' => 'Konfirmasi password Owner tidak cocok.',
        ];
    }
}
