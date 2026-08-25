<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use AngolaEmpresas\Scraper\Model\Company;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Exemplo de adapter para um guia de empresas angolano.
 *
 * Este e um ponto de partida: os seletores CSS sao meramente ilustrativos.
 * Ajuste-os para o site real (ex: GUIA, Rede Angola, portais de anuncios)
 * ou crie o seu proprio adapter copiando esta estrutura.
 */
final class GuiaEmpresasAdapter extends AbstractAdapter
{
    /**
     * @param array<string,string> $selectors seletores sobrepostos aos padroes
     */
    public function __construct(
        private string $url = 'https://www.guiaempresas.co.ao/',
        private string $strategy = 'light',
        private array $selectors = [],
    ) {
        $this->selectors = array_merge([
            'list' => '.listing-card',
            'name' => '.listing-card__title',
            'phone' => '.listing-card__phone',
            'email' => '.listing-card__email',
            'website' => '.listing-card__website',
            'address' => '.listing-card__address',
            'province' => '.listing-card__province',
            'sector' => '.listing-card__category',
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

        $companies = [];
        foreach ($list as $node) {
            $crawler = new Crawler($node);
            $companies[] = new Company(
                name: $this->clean($this->text($crawler, $this->selectors['name'])),
                phone: $this->clean($this->text($crawler, $this->selectors['phone'])),
                email: $this->clean($this->text($crawler, $this->selectors['email'])),
                website: $this->clean($this->attr($crawler, $this->selectors['website'], 'href')),
                address: $this->clean($this->text($crawler, $this->selectors['address'])),
                province: $this->clean($this->text($crawler, $this->selectors['province'])),
                sector: $this->clean($this->text($crawler, $this->selectors['sector'])),
                source: $this->url,
            );
        }

        return $companies;
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
        return $page->filter('a.pagination__next')->each(
            static fn (Crawler $a) => $a->link()->getUri()
        );
    }
}
