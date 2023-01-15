<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Client\ApiClient;
use Illuminate\Support\Facades\Http;
use Exception;
use Session;

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
                'client_id' => env('DOCUSIGN_CLIENT_ID'),
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
        $response = Http::withBasicAuth(env('DOCUSIGN_CLIENT_ID'), env('DOCUSIGN_CLIENT_SECRET'))
            ->post('https://account-d.docusign.com/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $request->code,
            ]);

        // dd(env('DOCUSIGN_CLIENT_ID'), env('DOCUSIGN_CLIENT_SECRET'), $response, $request->all());
        dd($response, $response->getBody()->getContents());

        $result = $response->json();

        dd($result);

        $request->session()->put('docusign_auth_code', $result['access_token']);

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
            $this->args = $this->getTemplateArgs();
            $args = $this->args;

            $envelope_args = $args["envelope_args"];

            /* Create the envelope request object */
            $envelope_definition = $this->makeEnvelopeFileObject($args["envelope_args"]);
            $envelope_api = $this->getEnvelopeApi();

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
            'email' => 'savani@gmail.com',
            'name' => 'savani',
            'recipient_id' => '1',
            'routing_order' => '1',
            'client_user_id' => $args['signer_client_id']
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
            'email_subject' => "Please sign this document sent from the ItSlutionStuff.com",
            'documents' => [$document],
            'recipients' => new \DocuSign\eSign\Model\Recipients(['signers' => [$signer]]),
            'status' => "sent",
        ]);

        return $envelopeDefinition;
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function getEnvelopeApi(): EnvelopesApi
    {
        $this->config = new Configuration();
        $this->config->setHost($this->args['base_path']);
        $this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->args['ds_access_token']);
        $this->apiClient = new ApiClient($this->config);

        return new EnvelopesApi($this->apiClient);
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    private function getTemplateArgs()
    {
        $args = [
            'account_id' => env('DOCUSIGN_ACCOUNT_ID'),
            'base_path' => env('DOCUSIGN_BASE_URL'),
            'ds_access_token' => Session::get('docusign_auth_code'),
            'envelope_args' => [
                'signer_client_id' => $this->signer_client_id,
                'ds_return_url' => route('docusign')
            ]
        ];

        return $args;
    }
}
