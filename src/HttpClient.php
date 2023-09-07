<?php

use React\Http\Browser;
use Infinex\API\APIException;

class HttpClient {
    private $loop;
    private $log;
    private $browser;
    
    function __construct($loop, $log) {
        $this -> loop = $loop;
        $this -> log = $log;
        
        $this -> browser = new Browser(null, $loop);
        
        $this -> log -> debug('Initialized legacy API client');
    }
    
    public function bind($amqp) {
        $th = $this;
        
        $amqp -> method(
            'api_legacy',
            function($body) use($th) {
                return $th -> request($body);
            }
        );
    }
    
    public function request($body) {
        $path = $body['path'];
        if(isset($body['origPath']))
            $path = $body['origPath'];
        
        return $this -> browser -> post(
            LEGACY_API_URL.$path,
            array(
                'Content-Type' => 'application/json'
            ),
            json_encode($body['body'])
        ) -> then(
            function($response) {
                $code = $response -> getStatusCode();
                if($code != 200)
                    throw new APIException($code, 'LEGACY_API_ERROR', $response -> getBody());
                
                return [
                    'status' => 200,
                    'body' => json_decode($response -> getBody(), true)
                ];
            }
        ) -> catch(
            function (\Exception $e) {
                throw new APIException(500, 'LEGACY_API_UNAVAILABLE', 'Connection with legacy API failed');
            }
        ) -> catch(
            function(APIException $e) {
                return [
                    'status' => $e -> getCode(),
                    'body' => [
                        'error' => [
                            'code' => $e -> getStrCode(),
                            'msg' => $e -> getMessage()
                        ]
                    ]
                ];
            }
        );
    }
}

?>