<?php

require __DIR__.'/HttpClient.php';

use React\Promise;

class App extends Infinex\App\App {
    private $client;
    
    function __construct() {
        parent::__construct('temp.legacy-api');
        
        $this -> client = new HttpClient(
            $this -> loop,
            $this -> log,
            $this -> amqp,
            LEGACY_API_URL
        );
    }
    
    public function start() {
        $th = $this;
        
        parent::start() -> then(
            function() use($th) {
                return $th -> client -> start();
            }
        ) -> catch(
            function($e) {
                $th -> log -> error('Failed start app: '.((string) $e));
            }
        );
    }
    
    public function stop() {
        $th = $this;
        
        $this -> client -> stop() -> then(
            function() use($th) {
                return Promise\all([
                    $th -> auth -> stop(),
                    $th -> router -> stop()
                ]);
            }
        ) -> then(
            function() use($th) {
                $th -> parentStop();
            }
        );
    }
    
    private function parentStop() {
        parent::stop();
    }
}

?>