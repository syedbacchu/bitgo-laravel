<?php

namespace Sdtech\BitgoApiLaravel\Service;

/*
	api.bitgo.com API Class - v2
	Copyright 2022 app.bitgo.com/ All rights reserved.
	License: MIT
*/

class BitgoApiLaravelService

{
    private $access_token;
    private $baseUrl;
    private $expressUrl;
    private $bitgoEnv;

    public function __construct()
    {
        $this->baseUrl = config('bitgolaravelapi.BITGO_API_BASE_URL');
        $this->access_token = config('bitgolaravelapi.BITGO_API_ACCESS_TOKEN');
        $this->expressUrl = config('bitgolaravelapi.BITGO_API_EXPRESS_URL');
        $this->bitgoEnv = config('bitgolaravelapi.BITGO_ENV');
    }

//DOCKER_COMMAND=docker run -it -p 3080:3080 bitgosdk/express:latest

    private function is_setup() {
        return (!empty($this->access_token) || !empty($this->baseUrl));
    }

    private function bitgoResponse($status,$message,$data = [])
    {
        return [
            'success' => $status,
            'message' => $message,
            'data' => $data
        ];
    }
    /**
     * api call
     * @param cmd end point
     * @param req request param
     * @param method GET/POST/DELETE/PUT etc
     * @param type general,express,notAuth
     *
     */
    private function api_call($cmd,$method,$req = "",$type = null) {
        if(is_null($type)) {
            $type = 'general';
        }
        if ($type == 'general') {
            if (!$this->is_setup()) {
                return $this->bitgoResponse(false,__('You have not called the Setup function with your api url and access token keys!'), []);
            }
        }

        // Generate the query string
        $post_data =!empty($req) ? $req : "";

        // Calculate the HMAC signature on the POST data
        $hmac = hash_hmac('sha512', $post_data, $this->access_token);
        if ($type == 'express') {
            $api_url = $this->expressUrl.$cmd;
        } else {
            $api_url = $this->baseUrl.$cmd;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->access_token
            ),
        ));
        $data = curl_exec($curl);
        curl_close($curl);

        if ($data !== FALSE) {
            if (PHP_INT_SIZE < 8 && version_compare(PHP_VERSION, '5.4.0') >= 0) {
                // We are on 32-bit PHP, so use the bigint as string option. If you are using any API calls with Satoshis it is highly NOT recommended to use 32-bit PHP
                $dec = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);
            } else {
                $dec = json_decode($data, TRUE);
            }
            if ($dec !== NULL && count($dec)) {
                if (isset($dec['error'])) {
                    return $this->bitgoResponse(false,$dec['error'], $dec);
                } else {
                    return $this->bitgoResponse(true,__('Api called data get successfully'), $dec);
                }
            } else {
                // If you are using PHP 5.5.0 or higher you can use json_last_error_msg() for a better error message
                return $this->bitgoResponse(false,'Unable to parse JSON result ('.json_last_error().')', []);
            }
        } else {
            return $this->bitgoResponse(false,'api calling error', []);
        }
    }


    /**
     * user login process
     * @param email
     * @param password
     * @param google_auth_otp
     *
     */
    public function userLogin($email,$password,$otp) {
        $req = array(
            'email' => $email,
            'password' => $password,
            'otp' => $otp,
        );
        return $this->api_call('/user/login','POST',json_encode($req),'notAuth');
    }
    /**
     * user profile
     * @param  access_token authorization key
     *
     */
    public function userProfile() {
        return $this->api_call('/user/me','GET','');
    }

    /**
     * Gets the current wallet list.
     * @param access_token authorization key
     */
    public function getWalletList($coin = null) {
        if (is_null($coin)) {
            return $this->api_call('/wallets','GET',[]);
        } else {
            return $this->api_call('/'.$coin.'/wallet','GET','');
        }
    }

    /**
     * Gets the current wallet.
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     */
    public function getWallet($coin,$walletId) {
        return $this->api_call('/'.$coin.'/wallet/'.$walletId,'GET', []);
    }

    /**
     * Gets the current wallet by address.
     * @param access_token authorization key
     * @param coin coin type
     * @param address wallet id
     */
    public function getWalletByAddress($coin,$address) {
        return $this->api_call('/'.$coin.'/wallet/address/'.$address,'GET', "");
    }

    /**
     * Gets the wallet address list.
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     */
    public function getWalletAddressList($coin,$walletId) {
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/addresses','GET', "");
    }

    /**
     * create wallet address.
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     * @param label wallet name
     */
    public function createWalletAddress($coin,$walletId,$chain,$label=null) {
        $req = array(
            'label' => !empty($label) ? $label : $coin,
            "chain" => $chain,
        );
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/address','POST', json_encode($req));
    }

    /**
     * build transaction
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     * @param amount
     * @param address receipant address
     * @param memo receipant address
     */
    public function buildTransaction($coin,$walletId,$amount,$address,$memo=null) {
        $sendAmount = $this->bitgo_divisibility_value(strtoupper($coin)) * $amount;
        $req = array(
            "recipients" => [
                'amount' => $sendAmount,
                'address' => $address,
                'memo' => !empty($memo) ? $memo : "string",
            ]
        );
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/tx/build','POST',$req);
    }

    /**
     * initiate transaction
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     * @param amount
     * @param address receipant address
     * @param memo receipant address
     */
    public function initiateTransaction($coin,$walletId,$amount,$address,$memo=null) {
        $sendAmount = $this->bitgo_divisibility_value(strtoupper($coin)) * $amount;
        $req = array(
            "recipients" => [
                'amount' => $sendAmount,
                'address' => $address,
                'memo' => !empty($memo) ? $memo : "string",
            ]
        );

        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/tx/initiate','POST',json_encode($req));
    }

    /**
     * send transaction
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     * @param txHex transaction hash get from build transaction
     */
    public function sendTransaction($coin,$walletId,$txHex) {
        $req = array(
            'halfSigned' => array(
                'txHex' => $txHex
            ),
        );
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/tx/send','POST', json_encode($req));
    }

    /**
     * send coin using express
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     * @param amount send amount
     * @param address receipant address
     * @param walletPassphrase wallet password
     */
    public function sendCoins($coin,$walletId,$amount,$address,$walletPassphrase) {
        $sendAmount = $this->bitgo_divisibility_value(strtoupper($coin)) * $amount;
        $req = array(
            'amount' => $sendAmount,
            'address' => $address,
            'walletPassphrase' => $walletPassphrase
        );
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/sendcoins','POST',json_encode($req),'express');
    }

    /**
     * transfer list
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     */
    public function transferList($coin,$walletId) {

        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/transfer','GET', "");
    }

    /**
     * transaction data
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     * @param txid transaction id
     */
    public function transferData($coin,$walletId,$txId) {

        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/transfer/'.$txId,'GET', "");
    }

    /**
     * pending approval
     * @param access_token authorization key
     *  @param pendingId pending id
     */
    public function pendingApproveList($pendingId = null) {
        if(is_null($pendingId)) {
            return $this->api_call('/pendingApprovals','GET', "");
        } else {
            return $this->api_call('/pendingApprovals/'.$pendingId,'GET', "");
        }
    }

    /**
     * pending approval item update
     * @param access_token authorization key
     * @param pendingId pending id
     * @param otp gauth otp
     * @param state status approved/rejected
     */
    public function pendingApproveItemUpdate($pendingId,$otp,$state) {
        $req = array(
            'otp' => $otp,
            'state' => $state,
        );
        return $this->api_call('/pendingApprovals/'.$pendingId,'PUT', json_encode($req));
    }
    /**
     * get estimate fess
     * @param access_token authorization key
     * @param coin coin type
     */
    public function getEstimateFees($coin) {
        return $this->api_call('/'.$coin.'/tx/fee','GET', "");
    }

    /**
     * get webhook list
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     */
    public function getWebhookList($coin,$walletId) {
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/webhooks','GET', "");
    }

    /**
     * add wallet webhook
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     * @param type  Enum: "transfer" "transaction" "pendingapproval" "address_confirmation" "lowFee",
     * @param allToken true, false,
     * @param url hook url
     * @param label hook title
     * @param numConfirmations number of confirmation > 0
     * @param listenToFailureStates boolean Whether or not to listen to failed transactions on chain.
     */

    public function addWebhook($coin,$walletId,$type,$allToken,$url,$label,$numConfirmations) {
        $req = array(
            "type" => $type,
            "allToken" => $allToken,
            "url" => $url,
            "label" => $label,
            "numConfirmations" => $numConfirmations,
            "listenToFailureStates" => true
        );
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/webhooks','POST', json_encode($req));
    }

    /**
     * remove wallet webhook
     * @param access_token authorization key
     * @param coin coin type
     * @param walletId wallet id
     */
    public function removeWalletWebhook($coin,$walletId,$type,$url,$hookId) {
        $req = array(
            "type" => $type,
            "url" => $url,
            "id" => $hookId
        );
        return $this->api_call('/'.$coin.'/wallet/'.$walletId.'/webhooks','DELETE', json_encode($req));
    }

    // get bitgo deposit divisibility value
    public function getDepositDivisibilityValue($coin)
    {
        return $this->bitgo_divisibility_value(strtoupper($coin));
    }


// bitgo divisibility
    public function bitgo_divisibility_value($input = null)
    {
        $env = $this->bitgoEnv ?? 'test';
        if ($env == 'test') {
            $output = $this->bitgo_divisibility_value_testnet();
        } else {
            $output = [
                "EOS"  => 10000,
                "ALGO" => 1000000,
                "STX"  => 1000000,
                "XTZ"  => 1000000,
                "TRX"  => 1000000,
                "XLM"  => 10000000,
                "BTC"  => 100000000,
                "BCH"  => 100000000,
                "BTG"  => 100000000,
                "DASH" => 100000000,
                "HBAR" => 100000000,
                "LTC"  => 100000000,
                "XRP"  => 100000000,
                "ZEC"  => 100000000,
                "CSPR" => 1000000000,
                "AVAX" => 1000000000000000000,
                "CELO" => 1000000000000000000,
                "ETH"  => 1000000000000000000,
                "RBTC" => 1000000000000000000,
            ];
        }

        if (is_null($input)) {
            return $output;
        } else {
            $result = 100000000;
            if (isset($output[$input])) {
                $result = $output[$input];
            }
            return $result;
        }
    }
// bitgo divisibility testnet
    public function bitgo_divisibility_value_testnet()
    {
        return [
            "TEOS"  => 10000,
            "TALGO" => 1000000,
            "TSTX"  => 1000000,
            "TXTZ"  => 1000000,
            "TTRX"  => 1000000,
            "TXLM"  => 10000000,
            "TBTC"  => 100000000,
            "TBCH"  => 100000000,
            "TDASH" => 100000000,
            "THBAR" => 100000000,
            "TLTC"  => 100000000,
            "TXRP"  => 100000000,
            "TZEC"  => 100000000,
            "TCSPR" => 1000000000,
            "TAVAX" => 1000000000000000000,
            "TCELO" => 1000000000000000000,
            "TETH"  => 1000000000000000000,
            "TRBTC" => 1000000000000000000,
        ];
    }
}
