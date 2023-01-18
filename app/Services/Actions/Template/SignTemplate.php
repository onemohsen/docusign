<?php

declare(strict_types=1);

namespace App\Services\Actions\Template;

use App\Services\Actions\Auth\GetDocusignToken;
use DocuSign\eSign\Client\ApiClient;
use Error;
use Illuminate\Support\Facades\Validator;

class SignTemplate
{
    public static function handle($request)
    {
        $requestValidate = Validator::make($request->all(), [
            'name' => ['required', 'min:5', 'string'],
            'email' => ['required', 'email'],
            'template' => ['required', 'string'],
        ]);

        $requestValidate->validate();

        $template = GetTemplateLists::handle($request->template);
        if (!$template) throw new Error("template not found");

        $envelopeDefinition = [
            'template_id' => $template['template_id'],
            'signer_email' => $request->email,
            'signer_name' => $request->name,
            'cc_email' => "",
            'cc_name' => "",
        ];

        $token = GetDocusignToken::handle();

        $args = [
            'envelope_args' => $envelopeDefinition,
            'account_id' => config('docusign.account_id'),
            'base_path' => config('docusign.base_url'),
            'ds_access_token' => $token
        ];

        $response = (new self)->worker($args);

        return $response;
    }


    private function make_envelope($args)
    {
        # Create the envelope definition with the template_id
        $envelope_definition = new \DocuSign\eSign\Model\EnvelopeDefinition([
            'status' => 'sent', 'template_id' => $args['template_id']
        ]);
        # Create the template role elements to connect the signer and cc recipients
        # to the template
        $signer = new \DocuSign\eSign\Model\TemplateRole([
            'email' => $args['signer_email'], 'name' => $args['signer_name'],
            'role_name' => 'signer'
        ]);
        # Create a cc template role.
        $cc = new \DocuSign\eSign\Model\TemplateRole([
            'email' => $args['cc_email'], 'name' => $args['cc_name'],
            'role_name' => 'cc'
        ]);
        # Add the TemplateRole objects to the envelope object
        $envelope_definition->setTemplateRoles([$signer, $cc]);
        return $envelope_definition;
    }

    private function worker($args)
    {
        $envelope_args = $args["envelope_args"];
        # Create the envelope request object
        $envelope_definition = $this->make_envelope($envelope_args);
        # Call Envelopes::create API method
        # Exceptions will be caught by the calling function
        $config = new \DocuSign\eSign\Configuration();
        $config->setHost($args['base_path']);
        $config->addDefaultHeader('Authorization', 'Bearer ' . $args['ds_access_token']);
        $api_client = new ApiClient($config);
        $envelope_api = new \DocuSign\eSign\Api\EnvelopesApi($api_client);
        $results = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);
        $envelope_id = $results->getEnvelopeId();
        return ['envelope_id' => $envelope_id];
    }
}
