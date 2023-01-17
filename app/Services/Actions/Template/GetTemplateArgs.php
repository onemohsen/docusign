<?php

declare(strict_types=1);

namespace App\Services\Actions\Template;

use App\Services\Actions\Auth\GetDocusignToken;

class GetTemplateArgs
{
    public static function handle($signerClientId = 1000)
    {
        $token = GetDocusignToken::handle();

        $args = [
            'account_id' => config('docusign.account_id'),
            'base_path' => config('docusign.base_url'),
            'ds_access_token' => $token,
            'envelope_args' => [
                'signer_client_id' => $signerClientId,
                'ds_return_url' => route('docusign')
            ]
        ];

        return $args;
    }
}
