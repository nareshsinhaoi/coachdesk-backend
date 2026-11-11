<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Seo extends BaseConfig
{
    public string $siteName    = 'CoachDesk';
    public string $siteTagline = 'Coaching Institute Management Software';
    public string $siteUrl     = 'https://coachdesk.in';           // ← change to your domain in production
    public string $defaultOgImage = 'https://coachdesk.in/og-image.png'; // 1200×630
    public string $twitterHandle  = '@coachdesk';                  // ← your handle
    public string $companyName    = 'ModuleLabs';
    public string $contactEmail   = 'info@modulelabs.in';
    public string $contactPhone   = '+91-8882331336';
    public string $addressCity    = 'Greater Noida';
    public string $addressState   = 'Uttar Pradesh';
    public string $addressCountry = 'IN';
    public string $addressPincode = '110091';
    public float  $latitude       = 28.4744;
    public float  $longitude      = 77.5040;
}