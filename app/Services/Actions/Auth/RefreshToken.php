<?php

declare(strict_types=1);

namespace App\Services\Actions\Auth;

use App\Jobs\RefreshTokenDocusignJob;
use App\Models\Option;
use App\Services\Api\ClientApi;
use DocuSign\eSign\Client\Auth\OAuth;
use Error;

class RefreshToken
{
    public static function handle()
    {
        $docusignAuth = Option::where('key', 'docusign_auth')->firstOrFail();

        if (!$docusignAuth) throw new Error('docusign auth is required, please authenticate again.');

        $refreshToken = $docusignAuth['value']['refresh_token'];

        $oAuth = new OAuth();
        $oAuth->setBasePath(config('docusign.base_url'));

        $clientApi = ClientApi::create(null, $oAuth);


        try {
            [$result] = $clientApi->refreshAccessToken(config('docusign.client_id'), config('docusign.client_secret'), $refreshToken);

            $data = [
                'access_token' => $result['access_token'] ?? '',
                'data' => $result['data'] ?? '',
                'expires_in' => $result['expires_in'] ?? '',
                'refresh_token' => $result['refresh_token'] ?? '',
                'scope' => $result['scope'] ?? '',
                'token_type' => $result['token_type'] ?? '',
            ];

            Option::updateOrCreate(['key' => 'docusign_auth'], ['key' => 'docusign_auth', 'value' => $data]);
            dispatch(new RefreshTokenDocusignJob())->delay(now()->addSeconds($result['expires_in'] - (60 * 30)));
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
