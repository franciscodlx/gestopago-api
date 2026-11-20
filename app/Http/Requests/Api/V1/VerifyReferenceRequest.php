<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyReferenceRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'idServicio' => ['required', 'integer', 'min:1'],
            'referencia' => ['required', 'string', 'max:64'],
        ];
    }
}
