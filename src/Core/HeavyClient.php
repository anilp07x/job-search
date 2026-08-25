<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Core;

use AngolaEmpresas\Scraper\Contracts\ClientInterface;

/**
 * Estrategia pesada: usa Symfony Panther (Chrome headless) para renderizar
 * JavaScript/Ajax antes de devolver o HTML.
 *
 * O pacote `symfony/panther` e sugerido (nao obrigatorio) e so precisa de
 * estar instalado se usar esta estrategia.
 *
 * Suporta autenticacao: se forem passados cookies de sessao, estes sao
 * registados no browser antes de navegar (util para portais com login).
 *
 * @param array{cookies?: array<string,string>, headers?: array<string,string>} $auth
 */
final class HeavyClient implements ClientInterface
{
    /**
     * @param array{cookies?: array<string,string>, headers?: array<string,string>} $auth
     */
    public function __construct(
        private array $pantherOptions = [],
        private array $pantherKernelOptions = [],
        private array $pantherManagerOptions = [],
        private array $auth = [],
    ) {
    }

    public function fetch(string $url): string
    {
        if (!class_exists(\Symfony\Component\Panther\Client::class)) {
            throw new \RuntimeException(
                'A estrategia pesada requer "symfony/panther". Instale com: composer require --dev symfony/panther'
            );
        }

        $client = \Symfony\Component\Panther\Client::createChromeClient(
            $this->pantherOptions['chrome_binary'] ?? null,
            $this->pantherOptions['arguments'] ?? null,
            $this->pantherKernelOptions,
            $this->pantherManagerOptions['base_uri'] ?? null,
        );

        try {
            if (!empty($this->auth['cookies']) && method_exists($client, 'getWebDriver')) {
                $host = (string) parse_url($url, PHP_URL_HOST);
                $client->get('https://' . $host);
                foreach ($this->auth['cookies'] as $name => $value) {
                    $client->getWebDriver()->manage()->addCookie([
                        'name' => (string) $name,
                        'value' => (string) $value,
                        'path' => '/',
                    ]);
                }
            }

            $client->get($url);
            $client->waitFor('body');

            return $client->getPageSource();
        } finally {
            $client->quit();
        }
    }
}
