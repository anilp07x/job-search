<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Tests;

use AngolaEmpresas\Scraper\Adapters\AngoEmpregoAdapter;
use AngolaEmpresas\Scraper\Adapters\AoEmpregosYoYotaAdapter;
use PHPUnit\Framework\TestCase;

final class JobAdapterTest extends TestCase
{
    private const WP_HTML = <<<HTML
    <html><body>
      <li class="job_listing">
        <div class="job-container">
          <div class="job-company-logo"><div class="company">Tech AO</div></div>
          <div class="job-content">
            <a href="/v/1"><div class="position"><h3>Dev PHP</h3></div></a>
            <div class="location">Luanda</div>
            <span class="job-type">full_time</span>
          </div>
        </div>
      </li>
      <li class="job_listing">
        <div class="job-container">
          <div class="job-company-logo"><div class="company">AngoContas</div></div>
          <div class="job-content">
            <a href="/v/2"><div class="position"><h3>Contabilista</h3></div></a>
            <div class="location">Benguela</div>
            <span class="job-type">contrato</span>
          </div>
        </div>
      </li>
    </body></html>
    HTML;

    private const YOYOTA_HTML = <<<HTML
    <html><body>
      <a href="https://www.ao.empregosyoyota.net/empregos/vaga-1" class="list-group-item list-group-item-action mb-3">
        <div class="d-flex w-100 justify-content-between"><h5 class="mb-1"><b>Analista de Dados</b></h5><small>Publicado em: 22-08-2026</small></div>
        <p class="mb-1">Empresa: African Parks - Iona, Angola</p>
        <small><i class="fa fa-map-marker"></i> Localização: <span>Namibe</span></small>
      </a>
      <a href="https://www.ao.empregosyoyota.net/empregos/vaga-2" class="list-group-item list-group-item-action mb-3">
        <div class="d-flex w-100 justify-content-between"><h5 class="mb-1"><b>Gestor de Vendas</b></h5><small>Publicado em: 21-08-2026</small></div>
        <p class="mb-1">Empresa: AngoRetail</p>
        <small><i class="fa fa-map-marker"></i> Localização: <span>Luanda</span></small>
      </a>
    </body></html>
    HTML;

    public function testAngoEmpregoParsesWpJobManager(): void
    {
        $jobs = (new AngoEmpregoAdapter())->parse(self::WP_HTML);

        $this->assertCount(2, $jobs);
        $this->assertSame('Dev PHP', $jobs[0]->title);
        $this->assertSame('Tech AO', $jobs[0]->company);
        $this->assertSame('Luanda', $jobs[0]->location);
        $this->assertSame('full_time', $jobs[0]->type);
        $this->assertSame('https://angoemprego.com', $jobs[0]->source);
    }

    public function testYoYotaParsesJobCards(): void
    {
        $jobs = (new AoEmpregosYoYotaAdapter())->parse(self::YOYOTA_HTML);

        $this->assertCount(2, $jobs);
        $this->assertSame('Analista de Dados', $jobs[0]->title);
        $this->assertSame('African Parks - Iona, Angola', $jobs[0]->company);
        $this->assertSame('Namibe', $jobs[0]->location);
        $this->assertSame('22-08-2026', $jobs[0]->postedAt);
        $this->assertSame('https://www.ao.empregosyoyota.net/empregos/vaga-1', $jobs[0]->url);
    }
}
