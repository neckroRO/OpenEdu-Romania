<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in([
                    UserRole::Teacher->value,
                    UserRole::Moderator->value,
                    UserRole::Admin->value,
                ]),
            ],
        ];
    }
}
