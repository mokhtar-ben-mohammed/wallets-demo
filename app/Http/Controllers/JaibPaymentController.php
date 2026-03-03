<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JaibPaymentController extends Controller
{
    //
    private $baseUrl='https://wallets.dev-options.com/api/jaib/v1';
    private $loginUrl;
    private $paymentUrl;
    public function  __construct(){
        $this->loginUrl=$this->baseUrl.'/TokenAuth/LogAPI';
        $this->paymentUrl=$this->baseUrl.'/BuyOnline/ExeBuy';
    }   

    public function payment($data){
       try{
        $loginResponse=$this->login();
        $data['pinApi']=base64_encode($loginResponse['pinApi']);
      
        if($loginResponse['isSuccess']){
            $token=$loginResponse['token'];
            $response=Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ])->post($this->paymentUrl,$data);
            
             $responseData=$response->json();
             if($response->successful())
              return array(
                'isSuccess'   => true,
                'message' => $responseData['result']['msg'],
                'referenceId' => $responseData['result']['referenceID'],
                'request_id' => $responseData['result']['requestID']
              );
                else if($response->failed())
                     return array(
                    'isSuccess'   => false,
                    'message' => $responseData['error']['message'],
                    'code' => $responseData['error']['code']);
        }else{
            return array(
                'isSuccess'   => false,
                'message' => $loginResponse['message']
            );  
        }
       }catch(\Exception $e){
        return array(
            'isSuccess'   => false,
            'message' => $e->getMessage()
        );
    }
    }

    public function login(){
        $loginData=array(
            'userName'=>'doAcademy',
            'password'=>'abc123#',
            'agentCode'=>'001'
        );
        try{
            $response=Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->loginUrl,$loginData);
            
             $responseData=$response->json();
             if($response->successful())
              return array(
                'isSuccess'   => true,
                'token' => $responseData['result']['accessToken'],
                'pinApi' => $responseData['result']['pinApi']
              );
                else if($response->failed())
                    
                     return array(
                    'isSuccess'   => false,
                    'message' => $responseData['error']['message']);
        }catch(\Exception $e){
            return array(
                'isSuccess'   => false,
                'message' => $e->getMessage()
            );  
        }
}
}
