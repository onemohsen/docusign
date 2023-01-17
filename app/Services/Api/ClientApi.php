<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Services\Actions\Template\GetTemplateArgs;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Client\Auth\OAuth;
use DocuSign\eSign\Configuration;

class ClientApi
{
    public static function create(Configuration $config = null, OAuth $auth = null)
    {
        if (!$config) {
            $config = new Configuration();
            $args = GetTemplateArgs::handle();
            $config->setHost($args['base_path']);
            $config->addDefaultHeader('Authorization', 'Bearer ' . $args['ds_access_token']);
        }

        $apiClient = new ApiClient($config, $auth);
        return $apiClient;
    }
}
