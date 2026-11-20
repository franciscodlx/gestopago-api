<?php

namespace App\Http\Requests\Api\V1;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class ListTransactionsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            /** Fecha inicial (inclusive), formato Y-m-d. */
            'from' => ['required', 'date_format:Y-m-d'],
            /** Fecha final (inclusive), formato Y-m-d. */
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function from(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('from')->toString()) ?: CarbonImmutable::today();
    }

    public function to(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('to')->toString()) ?: CarbonImmutable::today();
    }
}
