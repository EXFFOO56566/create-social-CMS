<?php

class MicrosoftTranslateText {

    private $azureKey;

    private $fromLanguage;

    private $toLanguage;

    public function __construct($azure_key = null, $to_language = null, $from_language = null) {
        if(isset($azure_key)) {
            $this->setAzureKey($azure_key);
        }
        if(isset($to_language)) {
            $this->setToLanguage($to_language);
        }
        if(isset($from_language)) {
            $this->setFromLanguage($from_language);
        }
        return $this;
    }

    public function setAzureKey($azure_key) {
        $this->azureKey = $azure_key;
        return $this;
    }

    public function getAzureKey() {
        $azure_key = $this->azureKey;
        return $azure_key;
    }

    public function setFromLanguage($from_language) {
        $this->fromLanguage = $from_language;
        return $this;
    }

    public function getFromLanguage() {
        $from_language = $this->fromLanguage;
        return $from_language;
    }

    public function setToLanguage($to_language) {
        $this->toLanguage = $to_language;
        return $this;
    }

    public function getToLanguage() {
        $to_language = $this->toLanguage;
        return $to_language;
    }

    private function getToken($azure_key) {
        $url = 'https://api.cognitive.microsoft.com/sts/v1.0/issueToken';
        $ch = curl_init();
        $data_string = json_encode('{body}');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Referer: https://www.google.com.ng/_/chrome/newtab-serviceworker.js',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36',
                'Content-Type: application/json',
                'Content-Length: '.strlen($data_string),
                'Ocp-Apim-Subscription-Key: '.$azure_key
            )
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $str_response = curl_exec($ch);
        curl_close($ch);
        return $str_response;
    }

    private function curlRequest($url, $auth_header = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $auth_header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $curl_response = curl_exec($ch);
        curl_close($ch);
        return $curl_response;
    }

    public function getTranslation($input_str, $to_language = null, $from_language = null, $azure_key = null) {
        if(isset($azure_key)) {
            $this->setAzureKey($azure_key);
        }
        $azure_key = $this->getAzureKey();
        $access_token = $this->getToken($azure_key);
        if(isset($to_language)) {
            $this->setToLanguage($to_language);
        }
        $to_language = $this->getToLanguage();
        if(isset($from_language)) {
            $this->setFromLanguage($from_language);
        }
        $from_language = $this->getFromLanguage();
        $params = 'text='.urlencode($input_str);
        $params .= ($to_language) ? '&to='.$to_language : '';
        $params .= ($from_language) ? '&from='.$from_language : '';
        $params .= '&appId=Bearer+'.$access_token;
        $translate_url = 'http://api.microsofttranslator.com/v2/Http.svc/Translate?'.$params;
        $auth_header = array(
            'Referer: https://www.google.com.ng/_/chrome/newtab-serviceworker.js',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36',
            'Content-Type: text/xml'
        );
        $curl_response = $this->curlRequest($translate_url, $auth_header);
        $xml_obj = simplexml_load_string($curl_response);
        $translated_str = $input_str;
        foreach ((array)$xml_obj[0] as $val) {
            $translated_str = $val;
        }
        return $translated_str;
    }
}