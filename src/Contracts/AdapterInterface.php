<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Contracts;

use Symfony\Component\DomCrawler\Crawler;

interface AdapterInterface
{
    /**
     * Estrategia de descarregamento: 'light' (Guzzle+DomCrawler) ou 'heavy' (Panther/Chrome).
     */
    public function getStrategy(): string;

    /**
     * URL (ou URL inicial) a ser extraida.
     */
    public function getUrl(): string;

    /**
     * Dados de autenticacao para portais que exigem login.
     *
     * @return array{cookies?: array<string,string>, headers?: array<string,string>}
     */
    public function getAuth(): array;

    /**
     * Converte o HTML da pagina numa lista de Company.
     *
     * @return \AngolaEmpresas\Scraper\Model\Company[]
     */
    public function parse(string $html): array;

    /**
     * Indica se o adapter deve seguir paginacao/ligacoes internas.
     */
    public function hasPagination(): bool;

    /**
     * Devolve o seletor CSS dos cartoes/blocos de cada empresa (se houver paginacao).
     */
    public function getListItemSelector(): ?string;

    /**
     * Devolve as ligacoes "proxima pagina" encontradas num Crawler de pagina.
     *
     * @return string[]
     */
    public function extractNextPageLinks(Crawler $page): array;
}
