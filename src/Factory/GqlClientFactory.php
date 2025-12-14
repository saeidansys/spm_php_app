<?php

namespace App\Factory;

use GraphQL\Client;

class GqlClientFactory
{

    private string $token;
    private string $url;

    public function __construct(string $url, string $token)
    {
        $this->token = $token;
        $this->url = $url;
    }

    public function createClient(): Client
    {
        return new Client($this->url, ['Authorization' => $this->token, 'API-Version' => '2023-10']);
    }

}
