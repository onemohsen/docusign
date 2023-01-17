<?php

namespace App\Http\Controllers;

use App\Jobs\RefreshTokenDocusignJob;
use App\Models\Option;
use App\Services\Actions\Api\EnvelopApi;
use App\Services\Actions\Template\GetTemplateArgs;
use App\Services\Actions\Template\SignTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class DocusignController extends Controller
{

    private $config, $args, $signer_client_id = 1000;
    /**
     * Show the html page
     *
     * @return render
     */
    public function index()
    {
        return view('docusign');
    }

    public function template()
    {
        return view('template');
    }

    /**
     * Connect your application to docusign
     *
     * @return url
     */
    public function connectDocusign()
    {
        try {
            $params = [
                'response_type' => 'code',
                'scope' => 'signature',
                'client_id' => config('docusign.client_id'),
                'state' => 'a39fh23hnf23',
                'redirect_uri' => route('docusign.callback'),
            ];
            $queryBuild = http_build_query($params);

            $url = "https://account-d.docusign.com/oauth/auth?";

            $botUrl = $url . $queryBuild;

            return redirect()->to($botUrl);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something Went wrong !');
        }
    }

    /**
     * This function called when you auth your application with docusign
     *
     * @return url
     */
    public function callback(Request $request)
    {
        $response = Http::withBasicAuth(config('docusign.client_id'), config('docusign.client_secret'))
            ->post('https://account-d.docusign.com/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $request->code,
            ]);

        $result = $response->json();

        Option::updateOrCreate(['key' => 'docusign_auth'], ['key' => 'docusign_auth', 'value' => $result]);

        RefreshTokenDocusignJob::dispatch()->delay(now()->addSeconds($result['expires_in'] - (60 * 30)));

        return redirect()->route('docusign')->with('success', 'Docusign Successfully Connected');
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function signDocument()
    {
        try {
            $this->args = GetTemplateArgs::handle($this->signer_client_id);
            $args = $this->args;

            $envelope_args = $args["envelope_args"];

            /* Create the envelope request object */
            $envelope_definition = $this->makeEnvelopeFileObject($args["envelope_args"]);
            $envelope_api = EnvelopApi::create();

            $api_client = new \DocuSign\eSign\client\ApiClient($this->config);
            $envelope_api = new \DocuSign\eSign\Api\EnvelopesApi($api_client);
            $results = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);
            $envelopeId = $results->getEnvelopeId();

            $authentication_method = 'None';
            $recipient_view_request = new \DocuSign\eSign\Model\RecipientViewRequest([
                'authentication_method' => $authentication_method,
                'client_user_id' => $envelope_args['signer_client_id'],
                'recipient_id' => '1',
                'return_url' => $envelope_args['ds_return_url'],
                'user_name' => 'savani', 'email' => 'savani@gmail.com'
            ]);

            $results = $envelope_api->createRecipientView($args['account_id'], $envelopeId, $recipient_view_request);

            return redirect()->to($results['url']);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    private function makeEnvelopeFileObject($args)
    {
        $docsFilePath = public_path('doc/demo_pdf_new.pdf');

        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        $contentBytes = file_get_contents($docsFilePath, false, stream_context_create($arrContextOptions));

        /* Create the document model */
        $document = new \DocuSign\eSign\Model\Document([
            'document_base64' => base64_encode($contentBytes),
            'name' => 'Example Document File',
            'file_extension' => 'pdf',
            'document_id' => 1
        ]);

        /* Create the signer recipient model */
        $signer = new \DocuSign\eSign\Model\Signer([
            'email' => 'onemohsen@gmail.com',
            'name' => 'onemohsen',
            'recipient_id' => '1',
            'routing_order' => '1',
            // 'client_user_id' => $args['signer_client_id']
        ]);

        /* Create a signHere tab (field on the document) */
        $signHere = new \DocuSign\eSign\Model\SignHere([
            'anchor_string' => '/sn1/',
            'anchor_units' => 'pixels',
            'anchor_y_offset' => '10',
            'anchor_x_offset' => '20'
        ]);

        /* Create a signHere 2 tab (field on the document) */
        $signHere2 = new \DocuSign\eSign\Model\SignHere([
            'anchor_string' => '/sn2/',
            'anchor_units' => 'pixels',
            'anchor_y_offset' => '40',
            'anchor_x_offset' => '40'
        ]);

        $signer->settabs(new \DocuSign\eSign\Model\Tabs(['sign_here_tabs' => [$signHere, $signHere2]]));

        $envelopeDefinition = new \DocuSign\eSign\Model\EnvelopeDefinition([
            'email_subject' => "Please sign this document sent from the Parspn",
            'documents' => [$document],
            'recipients' => new \DocuSign\eSign\Model\Recipients(['signers' => [$signer]]),
            'status' => "sent",
        ]);

        return $envelopeDefinition;
    }

    public function signTemplate(Request $request)
    {
        $result = SignTemplate::handle($request);

        return $result;
    }
}
