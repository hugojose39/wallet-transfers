<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Requests;

use Hyperf\Validation\Request\FormRequest;

final class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
        ];
    }
}
