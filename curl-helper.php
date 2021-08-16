<?php
class CurlHelper{
    private $url;
    private $method;
    private $data;
    public function __construct($url,$method,$data=null){
        $this->url = $url;
        $this->method = $method;
        $this->data = $data;
    }

    function makeRequest(){
        $curl = curl_init();
        if (!$curl) {
            die("Couldn't initialize a cURL handle");
        }
        curl_setopt($curl,CURLOPT_URL,$this->url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
        if($this->method=='POST'){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        if (curl_errno($curl))
        {
            echo 'cURL error: ' . curl_error($curl);
        }else{
            return $result;
        }
    }

}