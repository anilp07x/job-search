<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Base para portais baseados em WordPress + WP Job Manager
 * (ex.: angoemprego.com, careerjet.co.ao partilham esta estrutura).
 */
abstract class WordPressJobManagerAdapter extends AbstractJobAdapter
{
    /**
     * @param array<string,string> $selectors seletores sobrepostos aos padroes
     */
    public function __construct(
        private string $url,
        private string $strategy = 'light',
        private array $selectors = [],
    ) {
        $this->selectors = array_merge([
            'list' => '.job_listing',
            'title' => '.entry-title a, .entry-title',
            'url' => '.entry-title a, a.entry-title',
            'company' => '.company',
            'location' => '.location',
            'type' => '.job-type',
            'category' => '.job-categories',
            'logo' => '.company_logo',
        ], $this->selectors);
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
        $list = $page->filter($this->selectors['list']);

        if (0 === $list->count()) {
            return [];
        }

        $jobs = [];
        foreach ($list as $node) {
            $jobs[] = $this->buildJob(new Crawler($node), $this->selectors, $this->url);
        }

        return $jobs;
    }

    public function hasPagination(): bool
    {
        return true;
    }

    public function getListItemSelector(): ?string
    {
        return $this->selectors['list'];
    }

    public function extractNextPageLinks(Crawler $page): array
    {
        return $page->filter('.job-manager-pagination a.next, a.next.page-numbers')->each(
            static fn (Crawler $a) => $a->link()->getUri()
        );
    }
}
