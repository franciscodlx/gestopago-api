<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class EncryptPayloadRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            /** Objeto JSON a cifrar con AES-256-CBC tal como lo haría el servicio. */
            'payload' => ['required', 'array'],
        ];
    }
}
