<?php

namespace App\Http\Clients;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class UpstateClient
{
    /**
     * @throws RequestException
     */
    public function getEvents(): Collection
    {
        return Http::baseUrl(config('app.events_api_domain'))
            ->get('/api/gtc')
            ->throw()
            ->collect();
    }

    /**
     * @throws RequestException
     */
    public function getOrgs(): Collection
    {
        return Http::baseUrl(config('app.orgs_api_domain'))
            ->withQueryParameters([
                '_format' => 'json'
            ])
            ->asJson()
            ->get('rest/organizations')
            ->throw()
            ->collect();
    }
}
