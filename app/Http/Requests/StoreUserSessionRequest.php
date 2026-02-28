<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'token' => ['required', 'string', 'min:16'],
            'ip' => ['nullable', 'ip'],
            'agent' => ['nullable', 'string'],
        ];
    }
}
