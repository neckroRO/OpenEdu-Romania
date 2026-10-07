<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->route('user')),
            ],

            'role' => [
                'sometimes',
                Rule::in([
                    UserRole::Teacher->value,
                    UserRole::Moderator->value,
                    UserRole::Admin->value,
                ]),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
