<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FloosakPaymentController extends Controller
{
    //
    private $baseUrl='https://wallets.dev-options.com/api/floosak/v1';
    private $purchaseUrl;
    private $confirmUrl;
    public function  __construct(){
        $this->purchaseUrl=$this->baseUrl.'/merchant/p2mcl';
        $this->confirmUrl=$this->baseUrl.'/merchant/p2mcl/confirm';
    }

    public function initPayment(array $data){
        try{
           // return $data;
            $response=Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-channel'=>'merchant',
                'Authorization'=>'Bearer SnZWYTVBaHpqREV3bWJSNHNvdW91VEtWU1BSbVllZHg=.VVg3MnJuYms4cHVWQTI4azlZRW9Mckl1RXYwMXZCVGY=.ODVnYzZvb21HdnBQTDNTTTJ0NFBwRXpNVHk5bHJrTjdNeDltOVJUcDc0dEE1SUFpTkZHWVA2cnd6ZDQ4enBrSg=='
            ])->post($this->purchaseUrl,$data);
            
             $responseData=$response->json();
           
           if($response->successful())
              return array(
                'isSuccess'   => $responseData['is_success'],
                'referenceId' => $responseData['data']['reference_id'],
                'id'          => $responseData['data']['id']
            );
            else return array(
                'isSuccess'   => false,
                'message' => $responseData['message']
            );

        }catch(\Exception $e){
            return array(
                'isSuccess'   => false,
                'message' => $e->getMessage()
            );
        }
        
       

    }

    public function confirmPayment(array $data){
         try{
          
            $response=Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-channel'=>'merchant',
                'Authorization'=>'Bearer SnZWYTVBaHpqREV3bWJSNHNvdW91VEtWU1BSbVllZHg=.VVg3MnJuYms4cHVWQTI4azlZRW9Mckl1RXYwMXZCVGY=.ODVnYzZvb21HdnBQTDNTTTJ0NFBwRXpNVHk5bHJrTjdNeDltOVJUcDc0dEE1SUFpTkZHWVA2cnd6ZDQ4enBrSg=='
            ])->post($this->confirmUrl,$data);
           
             $responseData=$response->json();
           
           if($response->successful())
              return array(
                'isSuccess'   => $responseData['is_success'],
                'referenceId' => $responseData['data']['reference_id'],
                'id'          => $responseData['data']['id']
            );
            else return array(
                'isSuccess'   => false,
                'message' => $responseData['message']
            );

        }catch(\Exception $e){
            return array(
                'isSuccess'   => false,
                'message' => $e->getMessage()
            );
        }
       
    }
}
