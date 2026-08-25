<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Core;

use AngolaEmpresas\Scraper\Contracts\ClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Estrategia leve: descarrega o HTML estatico via Guzzle.
 * Ideal para sites de empresas em Angola sem renderizacao pesada de JavaScript.
 */
final class LightClient implements ClientInterface
{
    private GuzzleClient $guzzle;

    public function __construct(
        private array $options = [],
        ?GuzzleClient $guzzle = null,
    ) {
        $this->guzzle = $guzzle ?? new GuzzleClient(array_merge([
            'timeout' => 20,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'angola-empresas-scraper/0.1 (+https://github.com/angola-empresas/scraper)',
                'Accept-Language' => 'pt-PT,pt;q=0.9',
            ],
            'verify' => false,
        ], $this->options));
    }

    public function fetch(string $url): string
    {
        $requestOptions = [];

        $cookies = $this->options['cookies'] ?? [];
        if ($cookies) {
            $host = (string) parse_url($url, PHP_URL_HOST);
            $jar = new \GuzzleHttp\Cookie\CookieJar();
            foreach ($cookies as $name => $value) {
                $jar->setCookie(new \GuzzleHttp\Cookie\SetCookie([
                    'Name' => (string) $name,
                    'Value' => (string) $value,
                    'Domain' => $host,
                    'Path' => '/',
                ]));
            }
            $requestOptions['cookies'] = $jar;
        }

        $headers = $this->options['headers'] ?? [];
        if ($headers) {
            $requestOptions['headers'] = $headers;
        }

        try {
            $response = $this->guzzle->request('GET', $url, $requestOptions);
        } catch (GuzzleException $e) {
            throw new \RuntimeException(sprintf('Falha ao descarregar "%s": %s', $url, $e->getMessage()), 0, $e);
        }

        return (string) $response->getBody();
    }
}
