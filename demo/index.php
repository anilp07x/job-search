<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Demo - angola-empresas/scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { padding: 2rem; } .badge-light { background:#e9ecef; } </style>
</head>
<body>
<div class="container">
    <h1 class="mb-3">angola-empresas/scraper <small class="text-muted fs-5">demo de scraping de emprego</small></h1>

    <div class="alert alert-info">
        Esta pagina corre os adapters contra os <strong>sites ao vivo</strong> (dados reais, apenas
        estrategia <em>light</em>) ou contra <strong>fixtures locais</strong> (offline, fiavel).
        Adapters <em>heavy</em> (JS/Ajax) requerem Panther/Chrome e sao ignorados no modo live.
    </div>

    <div class="mb-4 btn-group">
        <a href="?mode=live" class="btn btn-outline-success">Testar ao vivo (dados reais)</a>
        <a href="?mode=fixture" class="btn btn-outline-primary">Testar com fixtures (offline)</a>
    </div>

    <?php
    require __DIR__ . '/../vendor/autoload.php';

    use AngolaEmpresas\Scraper\Adapters\JobAdapterRegistry;
    use AngolaEmpresas\Scraper\Adapters\AngoEmpregoAdapter;
    use AngolaEmpresas\Scraper\Scraper;

    $mode = $_GET['mode'] ?? 'live';
    $scraper = new Scraper();

    $jobartisCookie = null;
    if (($env = getenv('JOBARTIS_SESSION')) !== false) {
        $jobartisCookie = $env;
    } elseif (is_file(__DIR__ . '/.jobartis_session.txt')) {
        $jobartisCookie = trim((string) file_get_contents(__DIR__ . '/.jobartis_session.txt'));
    }

    foreach (JobAdapterRegistry::all() as $label => $class) {
        /** @var \AngolaEmpresas\Scraper\Adapters\AbstractJobAdapter $adapter */
        if ('Jobartis' === $label && $jobartisCookie) {
            $adapter = new $class(cookies: ['_jobartis_session_1' => $jobartisCookie]);
        } else {
            $adapter = new $class();
        }
        $strategy = $adapter->getStrategy();
        $jobs = [];
        $error = null;

        try {
            if ($mode === 'live') {
                if ($strategy === 'heavy') {
                    $error = 'Estrategia heavy: instale symfony/panther e use Chrome para testar ao vivo.';
                } else {
                    $jobs = $scraper->scrapeOnce($adapter);
                }
            } else {
                $file = __DIR__ . '/fixtures/' . strtolower(preg_replace('/[^a-z0-9]/i', '_', $label)) . '.html';
                if (is_file($file)) {
                    $html = file_get_contents($file);
                    $jobs = $adapter->parse($html);
                } else {
                    $error = 'Fixture em falta: ' . basename($file);
                }
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $count = count($jobs);
        $badge = $strategy === 'heavy' ? 'bg-warning text-dark' : 'bg-success';
        ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><strong><?= htmlspecialchars($label) ?></strong>
                    <span class="badge <?= $badge ?>"><?= htmlspecialchars($strategy) ?></span></span>
                <span class="badge bg-secondary"><?= $count ?> vagas</span>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-warning mb-0"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($count === 0): ?>
                    <div class="text-muted">Nenhuma vaga encontrada (verifique seletores).</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr><th>Titulo</th><th>Empresa</th><th>Local</th><th>Tipo</th><th>Fonte</th><th>Ver mais</th></tr></thead>
                            <tbody>
                            <?php foreach ($jobs as $j): ?>
                                <tr>
                                    <td><?= htmlspecialchars($j->title ?? '-') ?></td>
                                    <td><?= htmlspecialchars($j->company ?? '-') ?></td>
                                    <td><?= htmlspecialchars($j->location ?? '-') ?></td>
                                    <td><?= htmlspecialchars($j->type ?? '-') ?></td>
                                    <td><small><?= htmlspecialchars($j->source ?? '-') ?></small></td>
                                    <td>
                                        <?php if ($j->url): ?>
                                            <a href="<?= htmlspecialchars($j->url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary">Ver mais</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    ?>
</div>
</body>
</html>
