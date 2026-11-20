<?php

namespace Tests;

use App\Domain\GestoPago\Contracts\ApiKeyValidator;
use App\Domain\GestoPago\Contracts\CatalogRepository;
use App\Domain\GestoPago\Contracts\CommissionRepository;
use App\Domain\GestoPago\Contracts\TransactionRepository;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\InMemoryApiKeyValidator;
use Tests\Fakes\InMemoryCatalogRepository;
use Tests\Fakes\InMemoryCommissionRepository;
use Tests\Fakes\InMemoryTransactionRepository;

abstract class TestCase extends BaseTestCase
{
    public const string API_KEY = 'test-api-key';

    protected InMemoryTransactionRepository $transactions;

    protected InMemoryCatalogRepository $catalog;

    protected InMemoryCommissionRepository $commissions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ninguna prueba debe salir a la red.
        Http::preventStrayRequests();
    }

    /**
     * Sustituye los adaptadores de PostgreSQL por implementaciones en memoria.
     */
    protected function useInMemoryPersistence(): void
    {
        $this->transactions = new InMemoryTransactionRepository;
        $this->catalog = new InMemoryCatalogRepository;
        $this->commissions = new InMemoryCommissionRepository;

        $this->app->instance(TransactionRepository::class, $this->transactions);
        $this->app->instance(CatalogRepository::class, $this->catalog);
        $this->app->instance(CommissionRepository::class, $this->commissions);
        $this->app->instance(ApiKeyValidator::class, new InMemoryApiKeyValidator([self::API_KEY]));
    }

    /**
     * @return array<string, string>
     */
    protected function apiHeaders(): array
    {
        return ['X-Api-Key' => self::API_KEY, 'Accept' => 'application/json'];
    }
}
