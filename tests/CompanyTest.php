<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Tests;

use AngolaEmpresas\Scraper\Adapters\GenericAdapter;
use AngolaEmpresas\Scraper\Model\Company;
use PHPUnit\Framework\TestCase;

final class CompanyTest extends TestCase
{
    public function testToArrayOmitsNulls(): void
    {
        $company = new Company(name: 'Empresa XPTO', province: 'Luanda');

        $array = $company->toArray();

        $this->assertSame('Empresa XPTO', $array['name']);
        $this->assertSame('Luanda', $array['province']);
        $this->assertArrayNotHasKey('phone', $array);
        $this->assertArrayNotHasKey('email', $array);
    }

    public function testExtraIsIncluded(): void
    {
        $company = new Company(name: 'Y', extra: ['capital_social' => '100000']);

        $this->assertSame(['capital_social' => '100000'], $company->toArray()['extra']);
    }
}
