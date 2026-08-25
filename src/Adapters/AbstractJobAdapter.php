<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

use AngolaEmpresas\Scraper\Model\Job;

/**
 * Base para adapters de portais de emprego.
 *
 * Construtor aceita um mapa de seletores CSS e devolve Job[].
 * Campos comuns: title, company, location, province, type, category,
 * salary, description, url, postedAt. Qualquer outro vai para `extra`.
 */
abstract class AbstractJobAdapter extends AbstractAdapter
{
    /**
     * @param array<string,string> $selectors
     */
    protected function buildJob(\Symfony\Component\DomCrawler\Crawler $crawler, array $selectors, string $source): Job
    {
        $extra = [];
        $known = ['list', 'title', 'company', 'location', 'province', 'type', 'category', 'salary', 'description', 'url', 'postedAt'];

        foreach ($selectors as $field => $selector) {
            if (in_array($field, $known, true)) {
                continue;
            }
            $value = $this->clean($this->text($crawler, $selector));
            if (null !== $value) {
                $extra[$field] = $value;
            }
        }

        $url = $this->clean($this->attr($crawler, $selectors['url'] ?? 'a', 'href'));

        return new Job(
            title: $this->clean($this->text($crawler, $selectors['title'] ?? '.title')),
            company: $this->clean($this->text($crawler, $selectors['company'] ?? '.company')),
            location: $this->clean($this->text($crawler, $selectors['location'] ?? '.location')),
            province: $this->clean($this->text($crawler, $selectors['province'] ?? '.province')),
            type: $this->clean($this->text($crawler, $selectors['type'] ?? '.type')),
            category: $this->clean($this->text($crawler, $selectors['category'] ?? '.category')),
            salary: $this->clean($this->text($crawler, $selectors['salary'] ?? '.salary')),
            description: $this->clean($this->text($crawler, $selectors['description'] ?? '.description')),
            url: $url,
            postedAt: $this->clean($this->text($crawler, $selectors['postedAt'] ?? '.posted-at')),
            source: $source,
            extra: $extra,
        );
    }
}
