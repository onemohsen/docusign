<?php

declare(strict_types=1);

namespace App\Services\Actions\Auth;

use App\Models\Option;

class GetDocusignToken
{
    public static function handle()
    {
        $docusignAuth = Option::where('key', 'docusign_auth')->firstOrFail();
        $token = $docusignAuth['value']['access_token'];

        return $token;
    }
}
