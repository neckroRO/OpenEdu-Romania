<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectResourceVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_note' => [
                'required',
                'string',
                'max:5000',
            ],
        ];
    }
}
