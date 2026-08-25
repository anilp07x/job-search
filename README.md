# anilpedro07/scraper_vagas

Motor de **web scraping** para sites de empresas em Angola, escrito em PHP.

Escolhe automaticamente a melhor estrategia por site:

- **Leve (`light`)** — `Guzzle` + `Symfony DomCrawler`. Para sites estaticos/HTML.
- **Pesada (`heavy`)** — `Symfony Panther` (Chrome headless) para sites com JavaScript/Ajax.

Inclui um modelo de dados flexivel (`Company`) e adaptadores configuraveis,
facilitando a extracao de directorios e guias de empresas angolanas.

## Instalacao

```bash
composer require anilpedro07/scraper_vagas
```

Para usar a estrategia pesada (sites com JavaScript):

```bash
composer require --dev symfony/panther
```

## Uso basico

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
        'nif'     => '.nif',     // campo extra assume 'extra'
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

## Adapter de exemplo (Guia de Empresas)

```php
use AngolaEmpresas\Scraper\Adapters\GuiaEmpresasAdapter;

$adapter = new GuiaEmpresasAdapter('https://www.guiaempresas.co.ao/', 'light');
$companies = (new Scraper())->scrape($adapter);
```

> Os seletores do `GuiaEmpresasAdapter` sao ilustrativos. Ajuste-os ou crie o
> seu proprio adapter copiando `src/Adapters/AbstractAdapter.php`.

## Scraping de portais de emprego

Alem de empresas, o pacote inclui adapters para os portais de vagas em
Angola **verificados** (devolvem dados reais na pratica):

| Adapter | Site | Estrategia | Auth |
| --- | --- | --- | --- |
| `AngoEmpregoAdapter` | angoemprego.com (WP Job Manager) | light | nao |
| `AoEmpregosYoYotaAdapter` | ao.empregosyoyota.net/empregos | light | nao |
| `JobartisAdapter` | jobartis.com/vagas-emprego | light | cookie `_jobartis_session_1` |

```php
use AngolaEmpresas\Scraper\Scraper;
use AngolaEmpresas\Scraper\Adapters\AngoEmpregoAdapter;

$jobs = (new Scraper())->scrapeOnce(new AngoEmpregoAdapter());
foreach ($jobs as $job) {
    echo $job->title . ' @ ' . $job->company . ' (' . $job->location . ')' . PHP_EOL;
}
```

### Pagina de demonstracao (Bootstrap)

`demo/index.php` e uma pagina com Bootstrap que corre todos os adapters e
mostra os resultados numa tabela. Suporta modo **fixture** (offline, com
HTML de exemplo em `demo/fixtures/`) e modo **live** (scraping real, apenas
adapters `light`).

```bash
php -S 127.0.0.1:8123 -t demo
# abrir http://127.0.0.1:8123/?mode=fixture  (offline)
# abrir http://127.0.0.1:8123/?mode=live     (ao vivo, light)
```

## Portais que exigem login

Muitos portais so mostram as vagas depois de autenticacao. Ha duas abordagens:

### 1) HTML estatico atras de sessao (estrategia `light`)

Abra o portal no browser, faca login e copie os **cookies de sessao**
(DevTools → Application → Cookies, ex.: `sessionid`, `PHPSESSID`, `token`).
Devolva-os no `getAuth()` do seu adapter; o `LightClient` envia-os no pedido.

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
            'cookies' => [
                'sessionid' => 'COOKIE_COPIADO_DO_BROWSER',
                'csrftoken' => 'OUTRO_COOKIE',
            ],
            // ou, alternativamente, um header de Authorization:
            // 'headers' => ['Authorization' => 'Bearer SEU_TOKEN'],
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

### 2) Portal com JavaScript / login interativo (estrategia `heavy`)

Para sites que renderizam com JS ou exigem preencher o formulario de login,
use `composer require --dev symfony/panther` e implemente o login com o
browser headless antes de extrair. Exemplo de fluxo:

```php
use AngolaEmpresas\Scraper\Core\HeavyClient;
use Symfony\Component\Panther\Client;

$client = Client::createChromeClient();
$client->get('https://portal.co.ao/login');
$client->submitForm('Entrar', ['username' => '...', 'password' => '...']); //_passos reais do site_
$client->get('https://portal.co.ao/vagas');
$html = $client->getPageSource();
// passe $html ao seu adapter->parse($html)
```

> O LinkedIn bloqueia scraping e proibe nos ToS; use a API oficial. Para
> qualquer portal, respeite `robots.txt` e os Termos de Servico.

## Criar o seu proprio adapter

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

## Modelo de dados (`Company`)

Campos comuns: `name`, `phone`, `email`, `website`, `address`, `province`,
`municipality`, `sector`, `nif`, `source`. Qualquer outro campo vai para `extra`.

## Boas praticas

- Respeite `robots.txt` e os Termos de Servico de cada site.
- Adicione pausas (`sleep`) entre pedidos para nao sobrecarregar os servidores.
- Nao utilize os dados extraidos para spam ou violacao de privacidade.

## Testes

```bash
composer install
composer test
```

## Licenca

MIT
