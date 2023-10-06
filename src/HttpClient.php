<?php

use Infinex\Exceptions\Error;
use React\Http\Browser;

class HttpClient {
    private $loop;
    private $log;
    private $amqp;
    private $baseUrl;
    
    private $browser;
    
    function __construct($loop, $log, $amqp, $baseUrl) {
        $this -> loop = $loop;
        $this -> log = $log;
        $this -> amqp = $amqp;
        $this -> baseUrl = $baseUrl;
        
        $this -> browser = new Browser(null, $loop);
        
        $this -> log -> debug('Initialized HTTP client');
    }
    
    public function start() {
        $th = $this;
        
        return $this -> amqp -> method(
            'rest',
            function($body) use($th) {
                return $th -> request($body);
            }
        ) -> then(
            function() use($th) {
                $th -> log -> info('Started HTTP client');
            }
        ) -> catch(
            function($e) use($th) {
                $th -> log -> error('Failed to start HTTP client: '.((string) $e));
                throw $e;
            }
        );
    }
    
    public function stop() {
        $th = $this;
        
        return $this -> amqp -> unreg('rest') -> then(
            function() use ($th) {
                $th -> log -> info('Stopped HTTP client');
            }
        ) -> catch(
            function($e) use($th) {
                $th -> log -> error('Failed to stop HTTP client: '.((string) $e));
            }
        );
    }
    
    private function request($body) {
        return $this -> browser -> request(
            $body['method'],
            LEGACY_API_URL.$body['path'],
            array(
                'Content-Type' => 'application/json'
            ),
            json_encode($body['body'], JSON_UNESCAPED_SLASHES)
        ) -> catch(
            function (\Exception $e) {
                throw new Error('LEGACY_API_UNAVAILABLE', 'Connection with legacy API failed');
            }
        ) -> then(
            function($response) {
                $code = $response -> getStatusCode();
                if($code != 200)
                    throw new Error('LEGACY_API_ERROR', $response -> getBody());
                
                return [
                    'status' => 200,
                    'body' => json_decode($response -> getBody(), true)
                ];
            }
        );
    }
}

?>