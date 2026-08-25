<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use AngolaEmpresas\Scraper\Contracts\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Base comum para os adapters de sites angolanos.
 *
 * Fornece utilitarios de parsing (Crawler, extracao de texto/atributos,
 * limpeza) para que os adapters concretos foquem apenas nos seletores.
 */
abstract class AbstractAdapter implements AdapterInterface
{
    public function crawl(string $html): Crawler
    {
        return new Crawler($html);
    }

    public function getAuth(): array
    {
        return [];
    }

    protected function text(Crawler $node, string $selector, ?string $default = null): ?string
    {
        $found = $node->filter($selector);

        if (0 === $found->count()) {
            return $default;
        }

        $value = trim((string) $found->text());

        return '' === $value ? $default : $value;
    }

    protected function attr(Crawler $node, string $selector, string $attribute, ?string $default = null): ?string
    {
        $found = $node->filter($selector);

        if (0 === $found->count()) {
            return $default;
        }

        $value = trim((string) $found->attr($attribute));

        return '' === $value ? $default : $value;
    }

    protected function clean(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value) ?: null;
    }

    public function hasPagination(): bool
    {
        return false;
    }

    public function getListItemSelector(): ?string
    {
        return null;
    }

    public function extractNextPageLinks(Crawler $page): array
    {
        return [];
    }
}
