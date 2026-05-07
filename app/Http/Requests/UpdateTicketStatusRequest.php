<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                Ticket::STATUS_OPEN,
                Ticket::STATUS_TRIAGED,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_USER,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ])],
        ];
    }
}
