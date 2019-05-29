<?php

if(!class_exists('KunaApi'))
{
    class KunaApi {
        private $key = "";
        private $secret="";
        private $test = 0;

        function __construct($key,$secret)
        {
            $this->key = trim($key);
            $this->secret = trim($secret);
        }

        protected function sign($url, array $data, $method)
        {
          return hash_hmac('sha256', implode('|', [
                $method,
                '/api/v2'.$url,
                http_build_query($data),
            ]), $this->secret);
        }

        public function redeem_voucher($code)
        {
            //TODO: Add missing parameters (Search in https://kuna.io/documents/api)
            $request = $this->request('/kuna_codes/redeem',array('code'=>$code),true,'PUT');
            $res = @json_decode($request);
            // if(is_object($res) and $res->result == 1){
                return $request;
            // }
            /* или данные или пустота */

        }

        public function get_balans(){
          $request = $this->request('/members/me', array(),true,'GET');
          $res = @json_decode($request);
            $purses = array();
            foreach($res->accounts as $account){
              $currency = trim($account->currency);
              $value = trim($account->balance);
              if($currency=='uah'){
                $purses[$currency] = $value;
              }
            }
            return $purses;
          /* или массив или пустота */

        }

        public function check_voucher($code)
        {
            //TODO: Replace with valid URN '/kuna_codes/check/'
            $code=substr($code, 0, 5);
            $request = $this->request('/kuna_codes/check/',array('code'=>$code),false);
            $res = @json_decode($request);
            if($res->status=='valid'){
                return $res;
            }
            /* или данные или пустота */
        }

        public function request($api_name, $req = array(),$boo=true,$method='POST')
        {
            //$tonce= get_curl_parser('https://kuna.io/api/v2/timestamp', '', 'merchant', 'kuna')['output'].'000';
            $tonce  = (int) round(1000*microtime(true));
            $url    = "https://kuna.io/api/v2" . $api_name;

            if($boo){
                $req['access_key'] = $this->key;
                $req['tonce'] = $tonce;
                ksort($req);
                $req['signature'] = $this->sign($api_name, $req, $method) ;
                $post_data = http_build_query($req, '', '&');

                $url=$url.'?'.$post_data;
                $headers = array(
                    'Sign: ' . $req['signature'],
                    'Key: ' . $this->key,
                );
            }else{
                $url=$url.$req['code'];
            }

            $post_data = http_build_query($req, '', '&');
            $sign = hash_hmac('sha256', $post_data, $this->secret);

            

            static $ch = null;

            $c_options='' ;

            if ($boo){
                $c_options = array(
                    CURLOPT_CUSTOMREQUEST   => $method,
                    CURLOPT_POSTFIELDS      => $post_data,
                    CURLOPT_HTTPHEADER      => $headers,
                );
            }

            // if($boo){
            //     echo $url;
            //     exit;
            // }

            $result = get_curl_parser($url, $c_options, 'merchant', 'kuna');

            $err  = $result['err'];
            $out = $result['output'];

            if(!$err){
                if($this->test==1 ){
                    echo $out;
                    exit;
                }
                return $out;
            }
            elseif($this->test==1 ){
                echo $err;
                exit;
            }
        }
    }
}
