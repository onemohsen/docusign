<?php

declare(strict_types=1);

namespace App\Services\Actions\Template;

use App\Models\Option;
use App\Services\Actions\Common\ValidationThrow;
use App\Services\Actions\Auth\GetDocusignToken;
use Illuminate\Support\Facades\Http;

class GetTemplateLists
{
    public static function handle($name = null)
    {
        $template = Option::where('key', 'docusign_templates')->whereJsonContains('value', ['name' => $name])->first();

        if ($template) {
            return $template->value[0];
        }

        $accountId = config('docusign.account_id');
        $token = GetDocusignToken::handle();

        $url = "https://demo.docusign.net/restapi/v2.1/accounts/$accountId/templates/";

        try {
            $response = Http::withToken($token)->acceptJson()->get($url);
            $result = $response->json();

            $templateLists = $result['envelopeTemplates'];

            if ($name) {
                $list = array_filter($templateLists, function ($item) use ($name) {
                    return $item['name'] == $name;
                });
                if ($list) {
                    Option::updateOrCreate(['key' => 'docusign_templates'], ['key' => 'docusign_templates', 'value' => $list]);
                    return $list[0];
                } else {
                    ValidationThrow::handle(['template' => 'this template not found']);
                };
            }

            return $templateLists ?? null;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
