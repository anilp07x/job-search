<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Contracts;

interface ClientInterface
{
    /**
     * Descarrega o conteudo de uma URL e devolve o HTML.
     *
     * Na estrategia leve devolve o HTML estatico.
     * Na estrategia pesada devolve o HTML apos a execucao de JavaScript.
     */
    public function fetch(string $url): string;
}
