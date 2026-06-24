<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Requests;

use Hyperf\Validation\Request\FormRequest;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'document' => 'required|string|max:18',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'type' => 'required|in:common,merchant',
        ];
    }
}
