<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

/**
 * Registo dos adapters de emprego verificados (devolvem dados reais).
 */
final class JobAdapterRegistry
{
    /**
     * @return array<string, class-string<AbstractJobAdapter>>
     */
    public static function all(): array
    {
        return [
            'AngoEmprego' => AngoEmpregoAdapter::class,
            'Empregos YoYota' => AoEmpregosYoYotaAdapter::class,
            'Jobartis' => JobartisAdapter::class,
        ];
    }
}
