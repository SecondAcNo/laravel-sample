<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::in([
                Ticket::TYPE_ACCESS_REQUEST,
                Ticket::TYPE_INCIDENT,
                Ticket::TYPE_GENERAL_REQUEST,
            ])],
            'priority' => ['required', Rule::in([
                Ticket::PRIORITY_LOW,
                Ticket::PRIORITY_NORMAL,
                Ticket::PRIORITY_HIGH,
                Ticket::PRIORITY_URGENT,
            ])],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
        ];
    }
}
