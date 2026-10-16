<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssueRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'reason' => ['required', 'string', 'max:30'],
        ];
    }
}
