<?php

require __DIR__.'/HttpClient.php';

class App extends Infinex\App\Daemon {
    private $client;
    private $pdo;
    
    function __construct() {
        parent::__construct('temp.api-legacy');
        
        $this -> client = new HttpClient($this -> loop, $this -> log);
        
        $th = $this;
        $this -> amqp -> on('connect', function() use($th) {
            $th -> client -> bind($th -> amqp);
        });
    }
}

?>