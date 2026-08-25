<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use AngolaEmpresas\Scraper\Model\Job;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Portal ao.empregosyoyota.net (listagem em /empregos).
 *
 * Estrutura real: itens <a class="list-group-item"> com titulo em <h5><b>,
 * empresa em <p>Empresa: ...</p>, localizacao em <small><span> e data em
 * <small>Publicado em: ...</small>.
 */
final class AoEmpregosYoYotaAdapter extends AbstractJobAdapter
{
    public function __construct(
        private string $url = 'https://www.ao.empregosyoyota.net/empregos',
        private string $strategy = 'light',
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
        $items = $page->filter('a.list-group-item.list-group-item-action');

        if (0 === $items->count()) {
            return [];
        }

        $jobs = [];
        foreach ($items as $node) {
            $c = new Crawler($node);

            $companyRaw = $this->text($c, 'p.mb-1');
            $company = $companyRaw ? $this->clean(preg_replace('/^Empresa:\s*/i', '', $companyRaw)) : null;

            $postedRaw = $this->text($c, 'small');
            $postedAt = $postedRaw ? $this->clean(preg_replace('/^Publicado em:\s*/i', '', $postedRaw)) : null;

            $jobs[] = new Job(
                title: $this->clean($this->text($c, 'h5.mb-1 b, h5.mb-1')),
                company: $company,
                location: $this->clean($this->text($c, 'small span')),
                postedAt: $postedAt,
                url: $this->clean($c->attr('href')),
                source: $this->url,
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
        return 'a.list-group-item.list-group-item-action';
    }

    public function extractNextPageLinks(Crawler $page): array
    {
        return $page->filter('a.page-link, a[rel="next"]')->each(
            static fn (Crawler $a) => $a->link()->getUri()
        );
    }
}
