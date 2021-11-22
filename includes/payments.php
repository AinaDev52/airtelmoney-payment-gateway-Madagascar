<?php

/**
 * Created for Passion Prestige.
 * User: Aina Fenomanitra RAMAHERISON
 * Date: 15/11/2021
 */

require_once('config.php');

class Payments
{
    private $client_id;
    private $client_secret;
    private $site_url;
    private $token = null;

    /**
     * Payment Constructor
     * @param $client_id
     * @param $client_secret
     * @param $site_url
     * @param Params $parameter
     */

    public function __construct($params)
    {

        $this->client_id = $params['client_id'];
        $this->client_secret = $params['client_secret'];
        $this->site_url = $params['site_url'];

    }

    /**
     * @return mixed
     */

    private function getAccess() 
    {
        
        $parameter = new Params();
        if( $this->token != null )
        {
            return $this->token;
        }

        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials',
        );
        $responseCurl = $parameter->sendParams(Config::$URL_AUTH, "POST", array(), $params);
        $json = json_decode(
            $responseCurl
        );

        if( isset($json->error) )
        {
            throw new Exception($json->error . " : " . $json->error_description);
        }

        $this->token = $json->access_token;
        return $json->access_token;

    }

    /**
     * @param $url
     * @param array
     * @return bool|int|string
     */
    private function sendRequest($url, array $params_to_send)
    {

        $parameter = new Params();
        file_put_contents('log_params.txt', json_encode($params_to_send) . "\n", FILE_APPEND);

        $params = array(
            "site_url" => $this->site_url,
        );

        try {
            $headers = array("Authorization:Bearer " . $this->getAccess());
            $json = $parameter->sendParams($url, "POST", $headers, $params);
            $error = json_decode($json);
            if( isset($error->error) ) {
                throw new Exception($error->error . " : " . $error->error_description);
            }
        } catch (Exception $e) {
            throw new Exception($params['params'] . "Ce service est temporairement indisponible.");
        }

    }

    /**
     * @param $id_cart
     * @param $amount
     * @param $name
     * @param $reference
     * @return bool|int|string
     */
    public function initPayment($id_cart, $amount, $name, $reference)
    {

        $now = new DateTime();
        $params = array(
            "currency" => "MGA",
            "date" => $now->format('Y-m-d H:i:s'),
            "id_cart" => $id_cart,
            "amount" => $amount,
            "name" => $name,
            "reference" => $reference,
        );
        $id = $this->sendRequest(Config::$URL_PAIEMENT, $params);
        return $id;

    }

}
