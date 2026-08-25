<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Core;

use AngolaEmpresas\Scraper\Contracts\AdapterInterface;
use AngolaEmpresas\Scraper\Contracts\ClientInterface;

/**
 * Decide qual client usar com base na estrategia declarada pelo adapter:
 *  - 'light' -> LightClient (Guzzle + DomCrawler)
 *  - 'heavy' -> HeavyClient (Panther / Chrome headless)
 *
 * A autenticacao (cookies/headers) declarada no adapter e passada ao client.
 *
 * @param array{cookies?: array<string,string>, headers?: array<string,string>} $auth
 */
final class StrategyResolver
{
    /**
     * @param array{cookies?: array<string,string>, headers?: array<string,string>} $auth
     */
    public function resolve(string $strategy, array $auth = []): ClientInterface
    {
        return match (strtolower($strategy)) {
            'light', 'http', 'static' => new LightClient($auth),
            'heavy', 'browser', 'js', 'panther' => new HeavyClient(auth: $auth),
            default => throw new \InvalidArgumentException(sprintf('Estrategia desconhecida: "%s". Use "light" ou "heavy".', $strategy)),
        };
    }

    public function resolveFor(AdapterInterface $adapter): ClientInterface
    {
        return $this->resolve($adapter->getStrategy(), $adapter->getAuth());
    }
}
