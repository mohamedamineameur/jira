<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:todo,in_progress,review,done'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,critical'],
            'type' => ['sometimes', 'string', 'in:task,bug,story'],
            'assignee_id' => ['nullable', 'uuid', 'exists:users,id'],
            'reporter_id' => ['nullable', 'uuid', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
