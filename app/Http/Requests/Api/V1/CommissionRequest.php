<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class CommissionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_int($this->input('commission')) || is_float($this->input('commission'))) {
            $this->merge(['commission' => (string) $this->input('commission')]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            /** Comisión que cobra el negocio (valor que interpreta la función de BD). */
            'commission' => ['required', 'string', 'max:255'],
        ];
    }

    public function commission(): string
    {
        return $this->string('commission')->toString();
    }
}
