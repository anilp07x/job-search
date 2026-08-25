<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use AngolaEmpresas\Scraper\Model\Job;
use Symfony\Component\DomCrawler\Crawler;

/**
 * jobartis.com (portal de emprego africano, instancia Angola).
 *
 * Por padrao usa estrategia 'light' e aceita um cookie de sessao
 * (_jobartis_session_1) passado no construtor, devolvido em getAuth(),
 * para aceder a vagas que exigem autenticacao. Exemplo de uso:
 *
 *   new JobartisAdapter(cookies: ['_jobartis_session_1' => 'VALOR_DO_COOKIE']);
 */
final class JobartisAdapter extends AbstractJobAdapter
{
    /**
     * @param array<string,string> $cookies cookies de sessao (ex.: _jobartis_session_1)
     */
    public function __construct(
        private string $url = 'https://www.jobartis.com/vagas-emprego',
        private string $strategy = 'light',
        private array $cookies = [],
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

    public function getAuth(): array
    {
        return $this->cookies ? ['cookies' => $this->cookies] : [];
    }

    public function parse(string $html): array
    {
        $page = $this->crawl($html);
        $items = $page->filter('.job');

        if (0 === $items->count()) {
            return [];
        }

        $jobs = [];
        foreach ($items as $node) {
            $c = new Crawler($node);
            $details = $this->clean($this->text($c, '.job__details'));

            $jobs[] = new Job(
                title: $this->clean($this->text($c, '.job__title')),
                company: $this->clean($this->text($c, '.job__company')),
                description: $this->clean($this->text($c, '.job__description')),
                url: $this->clean($this->attr($c, '.job-link', 'href')),
                source: $this->url,
                extra: $details ? ['details' => $details] : [],
            );
        }

        return $jobs;
    }

    public function hasPagination(): bool
    {
        return true;
    }

    public function getListItemSelector(): ?string
    {
        return '.job';
    }

    public function extractNextPageLinks(Crawler $page): array
    {
        return $page->filter('a[rel="next"], .pagination a.next, a.next_page')->each(
            static fn (Crawler $a) => $a->link()->getUri()
        );
    }
}
