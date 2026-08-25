<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Tests;

use AngolaEmpresas\Scraper\Adapters\GenericAdapter;
use PHPUnit\Framework\TestCase;

final class GenericAdapterTest extends TestCase
{
    private function htmlFixture(): string
    {
        return <<<HTML
        <html lang="pt">
          <body>
            <div class="empresa">
              <h2 class="name">Construcoes Angola SA</h2>
              <span class="phone">+244 923 000 111</span>
              <span class="email">geral@constroi.co.ao</span>
              <a class="website" href="https://constroi.co.ao">site</a>
              <span class="address">Rua da Liga, 12</span>
              <span class="province">Luanda</span>
              <span class="sector">Construcao</span>
              <span class="fundacao">1998</span>
            </div>
            <div class="empresa">
              <h2 class="name">Pesca do Sul Lda</h2>
              <span class="phone">+244 912 222 333</span>
            </div>
          </body>
        </html>
        HTML;
    }

    public function testParsesListOfCompanies(): void
    {
        $adapter = new GenericAdapter(
            'https://exemplo.co.ao/empresas',
            'light',
            [
                'list' => '.empresa',
                'name' => '.name',
                'phone' => '.phone',
                'email' => '.email',
                'website' => '.website',
                'address' => '.address',
                'province' => '.province',
                'sector' => '.sector',
                'fundacao' => '.fundacao',
            ],
            ['source' => 'exemplo.co.ao'],
        );

        $companies = $adapter->parse($this->htmlFixture());

        $this->assertCount(2, $companies);

        $first = $companies[0];
        $this->assertSame('Construcoes Angola SA', $first->name);
        $this->assertSame('+244 923 000 111', $first->phone);
        $this->assertSame('geral@constroi.co.ao', $first->email);
        $this->assertSame('https://constroi.co.ao', $first->website);
        $this->assertSame('Luanda', $first->province);
        $this->assertSame('Construcao', $first->sector);
        $this->assertSame('exemplo.co.ao', $first->source);
        $this->assertSame(['fundacao' => '1998'], $first->extra);

        $second = $companies[1];
        $this->assertSame('Pesca do Sul Lda', $second->name);
        $this->assertNull($second->email);
    }

    public function testStrategyIsResolved(): void
    {
        $adapter = new GenericAdapter('https://x.ao', 'heavy', ['list' => '.e']);
        $this->assertSame('heavy', $adapter->getStrategy());
    }
}
