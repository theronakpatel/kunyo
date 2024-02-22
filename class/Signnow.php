<?php

/**
 * Creates a signing link for a document with the given document ID and payload.
 *
 * This method uses cURL to make a POST request to the specified URL with the document ID and payload as the request body.
 * The response is then parsed as JSON and the signing link is extracted from it.
 *
 * @param int $document_id The ID of the document to create a signing link for.
 * @param array $payload The payload containing the required data for creating the signing link.
 *   - 'firstname': The first name of the signer.
 *   - 'lastname': The last name of the signer.
 *   - 'email': The email of the signer.
 *   - 'role': The role of the signer.
 *
 * @return string The signing link for the document.
 */
class Signnow{
    public $basic_token;
    public $access_token;
    public $refresh_token;
    public $signow_url;
    public $username; 
    public $password;
    function __construct()
    {
        $this->basic_token = signow_basic;
        $this->username    = signow_username;
        $this->password    = signow_password;
        $this->signow_url  = signow_url;
    }
    /**
     * Authorizes the user and retrieves the access token.
     *
     * This method makes a POST request to the specified URL to authorize the user and obtain the access token.
     * It uses cURL to perform the request and sends the username, password, and grant_type as the request body parameters.
     * The response is then parsed as JSON and the access token is extracted from it.
     *
     * @return string The access token.
     */
    function authorize($sandbox = false){

        $curl = curl_init();
        if($sandbox){
             $this->username = CONFIG['sign_now_username_sandbox'];
             $this->password = CONFIG['sign_now_password_sandbox'];
             $this->basic_token = CONFIG['sign_now_token_sandbox'];
        }
        $postFields = array(
            'username' => $this->username,
            'password' => $this->password,
            'grant_type' => 'password',
        );


        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$this->basic_token,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);


        curl_close($curl);
        $response = json_decode($response, true);

        return $response['access_token'];

    }

    /**
     * Creates a new document from a template.
     *
     * This method takes a type parameter and based on its value, sets the document name and template id accordingly. It uses cURL to make a POST request to the specified URL, passing the
     * required data in the request body as JSON. It then receives the response, parses it as JSON, and extracts the document id from it.
     *
     * @param string $type The type of document to create. Possible values are "REO" and "Application".
     *
     * @return int The id of the created document.
     */
    function create_document_from_template($type, $sandbox = false){
        if($type == "ROE"){
            $document_name = "RonakTest6 Thunderbird - REO Form";

            $template_id = signow_roe_template;
            if($sandbox){
                $template_id = CONFIG['signnow_roe_form_production_tempalate_id'];
            }
        }
        if($type == "Application"){
            $document_name = "RonakTest6 Thunderbird - Application Form";
            $template_id = signow_application_template;
            if($sandbox){
                $template_id = CONFIG['signnow_application_form_sandbox_tempalate_id'];
            }
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/template/'.$template_id.'/copy',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "client_timestamp": '.time().',
                "document_name": "'.$document_name.'",
                "template_id": "'.$template_id.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->access_token
            ),
        ));

        $response = curl_exec($curl);

        $response = json_decode($response, true);

        return $response['id'];
    }


    /**
     * Retrieves the organization id of the user.
     *
     * This method makes a GET request to the specified URL to fetch the user's organization information. It uses cURL to perform the request.
     * The response is then parsed as JSON and the organization id is extracted from it.
     *
     * @return int The organization id.
     */
    function get_organization_id(){


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/user',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Bearer '.$this->access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        return $response['id'];

    }

    /**
     * Gets the role ID for a specific document.
     *
     * @param int $document_id The ID of the document.
     * @return int The role ID associated with the document.
     */
    function get_role_id($document_id){


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/document/'.$document_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);


    }

    /**
     * Creates embedded invites for a document using the given document ID, email, and role ID.
     *
     * @param int $document_id The ID of the document to create embedded invites for.
     * @param string $email The email of the invitee.
     * @param string $role_id The ID of the role for the invitee.
     * @return void
     */
    function create_embedded_invites($document_id, $email, $role_id ){


        $curl = curl_init();

        $data = [
            'invites' => [
                [
                    'email'       => $email,
                    'role_id'     => $role_id,
                    'order'       => '1',
                    'auth_method' => 'none',
                ],
            ],
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/v2/documents/'.$document_id.'/embedded-invites',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                                  "invites":  [
                                  {
                                    "email": "sasmat@trythunderbird.com",
                                    "role_id": "adc83bc9f5e344e69bcc31266f0cce3131e8ad0d",
                                    "order": 1,
                                    "auth_method": "email"
                                  }
                                ]
                                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer a7e54ecc824027b7ae28f24b77e2a170f82e8ce46285a35d03e0d9516cf6a3af',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }

    /**
     * Creates a signing link for a document with the given document ID and payload.
     *
     * @param int $document_id The ID of the document to create a signing link for.
     * @param array $payload The payload containing the required data for creating the signing link.
     *   - 'firstname': The first name of the signer.
     *   - 'lastname': The last name of the signer.
     *   - 'redirect_uri': The redirect URL after the signing process is complete.
     * @return string The signing link for the document.
     */
    function create_signing_link($document_id, $payload){
            $organization_id = $this->get_organization_id();


        $data = [
                    'document_id'       => $document_id,
                    'organization_id'     => $organization_id,
                    'firstname'       => $payload['firstname'],
                    'lastname'       => $payload['lastname'],
                    'redirect_uri' => $payload['redirect_url'],
                ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/link',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->access_token
            ),
        ));

        $response = curl_exec($curl);




        curl_close($curl);
        $response = json_decode($response, true);
        return $response['url'];

    }

    /**
     * Downloads a document using the given document ID.
     *
     * @param int $document_id The ID of the document to download.
     * @return string The download link for the document.
     */
    function download_document($document_id){


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/document/'.$document_id.'/download/link',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$this->access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);

        return $response['link'];

    }

    /**
     * adds prefilled data in a document using the given document ID.
     *
     * @param int $document_id The ID of the document to download.
     * @return array All fields.
     */
    function update_with_prefilled_document($document_id, $fields){

        $postFields = json_encode(array("fields" => $fields));

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->signow_url.'/v2/documents/'.$document_id.'/prefill-texts',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$this->access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);

    }
}
