<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use AngolaEmpresas\Scraper\Model\Job;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Adapter configuravel para qualquer portal de emprego.
 *
 * Igual ao GenericAdapter mas devolve Job[].
 *
 * Exemplo:
 *  new JobBoardAdapter('https://portal.co.ao/empregos', 'light', [
 *      'list'   => '.vaga',
 *      'title'  => '.vaga-titulo',
 *      'company'=> '.vaga-empresa',
 *      'location'=>' .vaga-local',
 *  ], ['source' => 'portal.co.ao']);
 */
class JobBoardAdapter extends AbstractJobAdapter
{
    /**
     * @param array<string,string> $selectors
     * @param array{pagination?:bool, next?:string, source?:string} $options
     */
    public function __construct(
        private string $url,
        private string $strategy,
        private array $selectors,
        private array $options = [],
    ) {
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function parse(string $html): array
    {
        $page = $this->crawl($html);
        $listSelector = $this->selectors['list'] ?? null;

        $nodes = null === $listSelector
            ? [$page]
            : $page->filter($listSelector);

        $source = $this->options['source'] ?? $this->url;
        $jobs = [];
        foreach ($nodes as $node) {
            $jobs[] = $this->buildJob(new Crawler($node), $this->selectors, $source);
        }

        return $jobs;
    }

    public function hasPagination(): bool
    {
        return (bool) ($this->options['pagination'] ?? false);
    }

    public function getListItemSelector(): ?string
    {
        return $this->selectors['list'] ?? null;
    }

    public function extractNextPageLinks(Crawler $page): array
    {
        $next = $this->options['next'] ?? null;
        if (null === $next) {
            return [];
        }

        return $page->filter($next)->each(static fn (Crawler $a) => $a->link()->getUri());
    }
}
