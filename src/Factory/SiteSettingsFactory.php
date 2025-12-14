<?php

namespace App\Factory;

class SiteSettingsFactory
{

    private string $siteUrl;
    private string $serviceUrl;
    private string $token;

    public function __construct(array $siteSettings)
    {
        $this->siteUrl = $siteSettings['host'];
        $this->serviceUrl = $siteSettings['service'];
        $this->token = $siteSettings['token'];
    }

    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    public function getServiceUrl(): string
    {
        return $this->serviceUrl;
    }

    public function getToken(): string
    {
        return $this->token;
    }

}
