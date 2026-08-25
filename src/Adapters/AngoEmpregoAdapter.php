<?php

declare(strict_types=1);

namespace AngolaEmpresas\Scraper\Adapters;

    /**
     * Portal de emprego angolano (WordPress + WP Job Manager).
     *
     * Seletores afinados para o tema real do angoemprego.com.
     */
    final class AngoEmpregoAdapter extends WordPressJobManagerAdapter
    {
        public function __construct(string $url = 'https://angoemprego.com', string $strategy = 'light')
        {
            parent::__construct($url, $strategy, [
                'list' => '.job_listing',
                'title' => '.position h3, .job-content h3',
                'url' => '.job-content a',
                'company' => '.company',
                'location' => '.location',
                'type' => '.job-type',
                'category' => '.category li',
            ]);
        }
    }
