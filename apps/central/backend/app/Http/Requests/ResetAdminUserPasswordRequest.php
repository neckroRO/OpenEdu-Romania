<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetAdminUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ];
    }
}
