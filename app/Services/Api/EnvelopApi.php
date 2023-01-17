<?php

declare(strict_types=1);

namespace App\Services\Actions\Api;

use App\Services\Api\ClientApi;
use DocuSign\eSign\Api\EnvelopesApi;

class EnvelopApi
{
    public static function create()
    {
        $apiClient = ClientApi::create();
        return new EnvelopesApi($apiClient);
    }
}
