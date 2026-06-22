<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Requests;

use Hyperf\Validation\Request\FormRequest;

final class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'required|numeric|min:0.01',
            'payer' => 'required|integer|min:1',
            'payee' => 'required|integer|min:1|different:payer',
        ];
    }
}
