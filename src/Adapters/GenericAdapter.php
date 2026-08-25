<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use AngolaEmpresas\Scraper\Model\Company;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Adapter generico e configuravel via seletores CSS.
 *
 * Permite apontar para qualquer site de empresas (incluindo angolanos)
 * sem escrever codigo: basta passar os seletores no construtor.
 *
 * Exemplo:
 *  new GenericAdapter('https://exemplo.co.ao/empresas', 'light', [
 *      'list'      => '.empresa',
 *      'name'      => '.nome',
 *      'phone'     => '.telefone',
 *      'email'     => '.email',
 *      'website'   => 'a.site',
 *      'address'   => '.morada',
 *      'province'  => '.provincia',
 *      'sector'    => '.setor',
 *      'nif'       => '.nif',
 *  ], ['pagination' => true, 'next' => 'a.seguinte']);
 */
final class GenericAdapter extends AbstractAdapter
{
    /**
     * @param array<string,string> $selectors mapa de campo => seletor CSS
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

        $companies = [];
        foreach ($nodes as $node) {
            $crawler = new Crawler($node);
            $companies[] = $this->buildCompany($crawler);
        }

        return $companies;
    }

    private function buildCompany(Crawler $crawler): Company
    {
        $extra = [];
        foreach ($this->selectors as $field => $selector) {
            if (in_array($field, ['list', 'name', 'phone', 'email', 'website', 'address', 'province', 'municipality', 'sector', 'nif'], true)) {
                continue;
            }
            $value = $this->clean($this->text($crawler, $selector));
            if (null !== $value) {
                $extra[$field] = $value;
            }
        }

        return new Company(
            name: $this->clean($this->text($crawler, $this->selectors['name'] ?? '.name')),
            phone: $this->clean($this->text($crawler, $this->selectors['phone'] ?? '.phone')),
            email: $this->clean($this->text($crawler, $this->selectors['email'] ?? '.email')),
            website: $this->clean($this->attr($crawler, $this->selectors['website'] ?? '.website', 'href')),
            address: $this->clean($this->text($crawler, $this->selectors['address'] ?? '.address')),
            province: $this->clean($this->text($crawler, $this->selectors['province'] ?? '.province')),
            municipality: $this->clean($this->text($crawler, $this->selectors['municipality'] ?? '.municipality')),
            sector: $this->clean($this->text($crawler, $this->selectors['sector'] ?? '.sector')),
            nif: $this->clean($this->text($crawler, $this->selectors['nif'] ?? '.nif')),
            source: $this->options['source'] ?? $this->url,
            extra: $extra,
        );
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
