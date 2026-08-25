<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper;

use AngolaEmpresas\Scraper\Contracts\AdapterInterface;
use AngolaEmpresas\Scraper\Contracts\ClientInterface;
use AngolaEmpresas\Scraper\Core\StrategyResolver;
use AngolaEmpresas\Scraper\Model\Company;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Facade principal do pacote.
 *
 * Escolhe a estrategia (light/heavy) conforme o adapter, descarrega o HTML
 * e converte-o em Company[]. Suporta paginacao automatica quando o adapter
 * a indicar.
 */
final class Scraper
{
    public function __construct(
        private ?StrategyResolver $resolver = null,
        private bool $followPagination = true,
        private int $maxPages = 10,
    ) {
        $this->resolver = $resolver ?? new StrategyResolver();
    }

    /**
     * @return Company[]
     */
    public function scrape(AdapterInterface $adapter): array
    {
        $client = $this->resolver->resolveFor($adapter);
        $results = [];

        $urls = [$adapter->getUrl()];
        $visited = [];
        $pages = 0;

        while ($urls !== [] && $pages < $this->maxPages) {
            $url = array_shift($urls);
            if (isset($visited[$url])) {
                continue;
            }
            $visited[$url] = true;

            $html = $client->fetch($url);
            $results = array_merge($results, $adapter->parse($html));

            if ($this->followPagination && $adapter->hasPagination()) {
                $page = new Crawler($html);
                foreach ($adapter->extractNextPageLinks($page) as $next) {
                    if (!isset($visited[$next])) {
                        $urls[] = $next;
                    }
                }
            }

            ++$pages;
        }

        return $results;
    }

    /**
     * Executa o scraping numa unica URL sem paginacao.
     *
     * @return Company[]
     */
    public function scrapeOnce(AdapterInterface $adapter, ?string $url = null): array
    {
        $client = $this->resolver->resolveFor($adapter);
        $html = $client->fetch($url ?? $adapter->getUrl());

        return $adapter->parse($html);
    }

    public function clientFor(AdapterInterface $adapter): ClientInterface
    {
        return $this->resolver->resolveFor($adapter);
    }
}
