<?php

namespace App\Console\Commands;

use App\Application\GestoPago\SyncProductCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gestopago:sync-products')]
#[Description('Descarga el catálogo de productos de Gesto Pago y lo guarda en la base de datos')]
final class SyncProductsCommand extends Command
{
    public function handle(SyncProductCatalog $syncProductCatalog): int
    {
        $synced = $syncProductCatalog->handle();

        $this->components->info("Productos sincronizados: {$synced}");

        return self::SUCCESS;
    }
}
