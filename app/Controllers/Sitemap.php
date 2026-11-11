<?php

namespace App\Controllers;

class Sitemap extends BaseController
{
    public function index()
    {
        $base   = 'https://coachdesk.in';
        $now    = date('Y-m-d');

        $pages = [
            ['loc' => '/',         'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => '/home',     'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/#features','priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/#pricing', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => '/#faq',     'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/admin/register', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/admin/login',    'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as $p) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$base}{$p['loc']}</loc>\n";
            $xml .= "    <lastmod>{$now}</lastmod>\n";
            $xml .= "    <changefreq>{$p['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$p['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $this->response
            ->setContentType('application/xml')
            ->setBody($xml);
    }
}