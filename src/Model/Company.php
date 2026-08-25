<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Model;

use AngolaEmpresas\Scraper\Contracts\CompanyInterface;

/**
 * Modelo flexivel de empresa.
 *
 * Tem campos comuns (nome, contactos, localizacao, fisco) e um array
 * `extra` para qualquer campo especifico de um site angolano.
 */
final class Company implements CompanyInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $website = null,
        public ?string $address = null,
        public ?string $province = null,
        public ?string $municipality = null,
        public ?string $sector = null,
        public ?string $nif = null,
        public ?string $source = null,
        public array $extra = [],
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            'province' => $this->province,
            'municipality' => $this->municipality,
            'sector' => $this->sector,
            'nif' => $this->nif,
            'source' => $this->source,
            'extra' => $this->extra ?: null,
        ], static fn ($v) => $v !== null);
    }
}
