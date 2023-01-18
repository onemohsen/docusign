<?php

declare(strict_types=1);

namespace App\Services\Actions\Template;

use App\Models\Option;
use App\Services\Actions\Common\ValidationThrow;
use App\Services\Api\ClientApi;
use DocuSign\eSign\Api\TemplatesApi;

class GetTemplateLists
{
    public static function handle($name = null)
    {
        $template = Option::where('key', 'docusign_templates')->whereJsonContains('value', ['name' => $name])->first();
        // dd($template)->value;
        if ($template) {
            return $template->value[0];
        }

        $accountId = config('docusign.account_id');

        $apiClient = ClientApi::create();
        $templateApi = new TemplatesApi($apiClient);

        $result = $templateApi->listTemplates($accountId);
        $templateLists = $result['envelope_templates'];

        if ($name) {

            $list = array_filter($templateLists, function ($item) use ($name) {
                return $item['name'] == $name;
            });

            if (!empty($list)) {
                $objectConvertToArrayList = [];
                foreach ((array)$list as $listItem) {
                    $objectConvertToArrayList[] = array_values((array)$listItem)[0];
                }

                Option::updateOrCreate(['key' => 'docusign_templates'], ['key' => 'docusign_templates', 'value' => $objectConvertToArrayList]);
                return $list[0];
            } else {
                ValidationThrow::handle(['template' => 'this template not found']);
            };
        }

        try {
            // $response = Http::withToken($token)->acceptJson()->get($url);
            // $result = $response->json();
            return $templateLists ?? null;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
