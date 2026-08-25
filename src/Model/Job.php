<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Model;

use AngolaEmpresas\Scraper\Contracts\CompanyInterface;

/**
 * Modelo flexivel de vaga de emprego.
 *
 * Campos comuns (titulo, empresa, local, tipo, salario, etc.) e um array
 * `extra` para qualquer campo especifico de um portal angolano.
 */
final class Job implements CompanyInterface
{
    public function __construct(
        public ?string $title = null,
        public ?string $company = null,
        public ?string $location = null,
        public ?string $province = null,
        public ?string $type = null,
        public ?string $category = null,
        public ?string $salary = null,
        public ?string $description = null,
        public ?string $url = null,
        public ?string $postedAt = null,
        public ?string $source = null,
        public array $extra = [],
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'company' => $this->company,
            'location' => $this->location,
            'province' => $this->province,
            'type' => $this->type,
            'category' => $this->category,
            'salary' => $this->salary,
            'description' => $this->description,
            'url' => $this->url,
            'postedAt' => $this->postedAt,
            'source' => $this->source,
            'extra' => $this->extra ?: null,
        ], static fn ($v) => $v !== null);
    }
}
