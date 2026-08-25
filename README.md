# scraper_vagas

> Motor de **web scraping** em PHP para portais de emprego e empresas em Angola — com estratégia automática **leve** (HTTP estático) ou **pesada** (Chrome headless) e adaptadores prontos a usar.

[![Packagist](https://img.shields.io/badge/Packagist-anilpedro07%2Fscraper_vagas-2196F3?logo=packagist)](https://packagist.org/packages/anilpedro07/scraper_vagas)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Symfony](https://img.shields.io/badge/Symfony-DomCrawler%20%2F%20CssSelector-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![Guzzle](https://img.shields.io/badge/Guzzle-HTTP%20Client-FF6F61)](https://docs.guzzlephp.org/)
[![Panther](https://img.shields.io/badge/Panther-Chrome%20Headless-000000)](https://github.com/symfony/panther)
[![Tests](https://img.shields.io/badge/tests-6%20passing-brightgreen)](https://github.com/anilp07x/job-search)

## Tecnologias

<p align="left">
  <a href="https://www.php.net/"><img src="https://cdn.simpleicons.org/php/777BB4" alt="PHP" height="38"></a>
  <a href="https://getcomposer.org/"><img src="https://cdn.simpleicons.org/composer/885630" alt="Composer" height="38"></a>
  <a href="https://symfony.com/"><img src="https://cdn.simpleicons.org/symfony/000000" alt="Symfony" height="38"></a>
  <a href="https://docs.guzzlephp.org/"><img src="https://cdn.simpleicons.org/guzzle/ff6f61" alt="Guzzle" height="38"></a>
  <a href="https://github.com/symfony/panther"><img src="https://cdn.simpleicons.org/selenium/43B02A" alt="Panther / Chrome" height="38"></a>
  <a href="https://laravel.com/"><img src="https://cdn.simpleicons.org/laravel/FF2D20" alt="Laravel" height="38"></a>
</p>

## Funcionalidades

- **Estratégia automática** — o `StrategyResolver` escolhe entre cliente leve (Guzzle + Symfony DomCrawler) ou pesado (Symfony Panther / Chrome headless) conforme o adapter.
- **Adaptadores verificados** para os principais portais de emprego angolanos (ver tabela abaixo).
- **Modelos de dados flexíveis** — `Company` e `Job` com campos comuns + array `extra` para metadados específicos.
- **Autenticação** — suporte a cookies/headers de sessão para portais que exigem login.
- **Paginação** — seguimento automático de "próxima página" quando o adapter o indica.
- **Demo Bootstrap** — página de exemplo que corre os adapters e apresenta os resultados numa tabela.

## Instalação

```bash
composer require anilpedro07/scraper_vagas
```

Para usar a estratégia pesada (sites com JavaScript/Ajax):

```bash
composer require --dev symfony/panther
```

## Início rápido

### Empresas (adapter genérico/configurável)

```php
use AngolaEmpresas\Scraper\Scraper;
use AngolaEmpresas\Scraper\Adapters\GenericAdapter;

$adapter = new GenericAdapter(
    'https://exemplo.co.ao/empresas',
    'light',                 // ou 'heavy' para sites com JS
    [
        'list'    => '.empresa',
        'name'    => '.name',
        'phone'   => '.phone',
        'email'   => '.email',
        'website' => '.website',
        'address' => '.address',
        'province'=> '.province',
        'sector'  => '.sector',
        'nif'     => '.nif',     // campo extra vai para 'extra'
    ],
    ['source' => 'exemplo.co.ao'],
);

$scraper = new Scraper();
$companies = $scraper->scrape($adapter);

foreach ($companies as $company) {
    echo $company->name . ' -> ' . $company->phone . PHP_EOL;
    print_r($company->toArray());
}
```

### Vagas de emprego (adapter verificado)

```php
use AngolaEmpresas\Scraper\Scraper;
use AngolaEmpresas\Scraper\Adapters\AngoEmpregoAdapter;

$jobs = (new Scraper())->scrapeOnce(new AngoEmpregoAdapter());
foreach ($jobs as $job) {
    echo $job->title . ' @ ' . $job->company . ' (' . $job->location . ')' . PHP_EOL;
}
```

## Adaptadores verificados

Estes adapters foram testados e devolvem **dados reais**:

| Adapter | Site | Estratégia | Autenticação |
| --- | --- | --- | --- |
| `AngoEmpregoAdapter` | angoemprego.com (WP Job Manager) | `light` | não |
| `AoEmpregosYoYotaAdapter` | ao.empregosyoyota.net/empregos | `light` | não |
| `JobartisAdapter` | jobartis.com/vagas-emprego | `light` | cookie `_jobartis_session_1` |

Para o `JobartisAdapter` com sessão:

```php
use AngolaEmpresas\Scraper\Adapters\JobartisAdapter;

$adapter = new JobartisAdapter(cookies: [
    '_jobartis_session_1' => 'VALOR_DO_COOKIE_COPIADO_DO_BROWSER',
]);
```

## Portais que exigem login

Muitos portais só mostram as vagas após autenticação. Duas abordagens:

### 1) HTML estático atrás de sessão (estratégia `light`)

Copie os **cookies de sessão** (DevTools → Application → Cookies, ex.: `sessionid`, `PHPSESSID`) e devolva-os em `getAuth()`; o `LightClient` envia-os no pedido.

```php
use AngolaEmpresas\Scraper\Adapters\AbstractJobAdapter;
use AngolaEmpresas\Scraper\Model\Job;

final class PortalAuthAdapter extends AbstractJobAdapter
{
    public function getStrategy(): string { return 'light'; }
    public function getUrl(): string     { return 'https://portal.co.ao/vagas'; }

    public function getAuth(): array
    {
        return [
            'cookies' => ['sessionid' => 'COOKIE_COPIADO_DO_BROWSER'],
            // ou: 'headers' => ['Authorization' => 'Bearer SEU_TOKEN'],
        ];
    }

    public function parse(string $html): array
    {
        $page = $this->crawl($html);
        return $page->filter('.vaga')->each(fn ($n) => new Job(
            title: $this->clean($this->text($n, '.titulo')),
            company: $this->clean($this->text($n, '.empresa')),
        ));
    }
}

$jobs = (new \AngolaEmpresas\Scraper\Scraper())->scrapeOnce(new PortalAuthAdapter());
```

### 2) Portal com JavaScript / login interativo (estratégia `heavy`)

```php
use Symfony\Component\Panther\Client;

$client = Client::createChromeClient();
$client->get('https://portal.co.ao/login');
$client->submitForm('Entrar', ['username' => '...', 'password' => '...']);
$client->get('https://portal.co.ao/vagas');
$html = $client->getPageSource();
// passe $html ao adapter->parse($html)
```

> O LinkedIn bloqueia scraping e proíbe nos ToS — use a API oficial. Para qualquer portal, respeite `robots.txt` e os Termos de Serviço.

## Criar o seu próprio adapter

```php
use AngolaEmpresas\Scraper\Adapters\AbstractAdapter;
use AngolaEmpresas\Scraper\Model\Company;

final class MeuSiteAdapter extends AbstractAdapter
{
    public function getStrategy(): string { return 'light'; }
    public function getUrl(): string     { return 'https://meusite.co.ao'; }

    public function parse(string $html): array
    {
        $page = $this->crawl($html);
        return $page->filter('.empresa')->each(fn ($node) => new Company(
            name: $this->clean($this->text($node, '.nome')),
            phone: $this->clean($this->text($node, '.tel')),
        ));
    }
}
```

## Demonstração (Bootstrap)

`demo/index.php` é uma página com Bootstrap que corre os adapters e apresenta os resultados numa tabela, com botão **"Ver mais"** por vaga (abre o site de destino). Suporta:

- `?mode=live` — scraping real (adapters `light`, com cookie para o Jobartis)
- `?mode=fixture` — offline, com HTML de exemplo em `demo/fixtures/`

```bash
php -S 127.0.0.1:8123 -t demo
# http://127.0.0.1:8123/            (ao vivo)
# http://127.0.0.1:8123/?mode=fixture
```

> A pasta `demo/` não é versionada (está no `.gitignore`).

## Testes

```bash
composer install
composer test
```

## Roadmap

- [ ] Exemplo de integração **Laravel** (`examples/laravel-demo`): ServiceProvider, Artisan command, Eloquent model, agendamento e filas.
- [ ] Mais adaptadores verificados de portais angolanos.

## Boas práticas

- Respeite `robots.txt` e os Termos de Serviço de cada site.
- Adicione pausas (`sleep`) entre pedidos para não sobrecarregar os servidores.
- Não utilize os dados extraídos para spam ou violação de privacidade.

## Licença

[MIT](LICENSE) © anilpedro07
