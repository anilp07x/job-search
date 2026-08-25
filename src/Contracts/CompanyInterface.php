<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Contracts;

interface CompanyInterface
{
    public function toArray(): array;
}
