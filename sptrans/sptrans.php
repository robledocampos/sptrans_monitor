<?php

Namespace Sptrans;

use HttpApiClient\ApiClient;

class sptrans{

    private $apiUrl = "http://api.olhovivo.sptrans.com.br/v2.1/";
    private $token = "13b402205e1d4578434ea3b9c4599651f769b7d9f8aa6e8257de21b74efd17a7";
    private $debug = 0;
    private $cookie = null;
    private $headers = ['Content-Length: 0'];
    private $ssl = false;

    public function auth(){
        $parameters = [
            "token" => $this->token
        ];
        $apiClient = new ApiClient($this->apiUrl);
        $result = $apiClient->call("Login/Autenticar/", $parameters, null, $this->headers, null, "POST", $this->ssl);
        $cookies = $apiClient::getCookies(";", $result['header']['Set-Cookie']);
        $this->cookie = $cookies[0];

        return $result;
    }

    public function searchLines($query){
        $parameters = [
            "termosBusca" => $query
        ];
        $apiClient = new ApiClient($this->apiUrl);
        $result = $apiClient->call("Linha/Buscar/", $parameters, null, $this->headers, $this->cookie, "GET", $this->ssl);

        return $result;
    }

    public function getLinePositions($lineCode){
        $parameters = [
            "codigoLinha" => $lineCode
        ];
        $apiClient = new ApiClient($this->apiUrl);
        $result = $apiClient->call("Posicao/", $parameters, null, $this->headers, $this->cookie, "GET", $this->ssl);

        return $result;
    }

    public function getLines(){
        if($this->auth()){
            echo "Autorized\n";
            $database = new database();
            echo "Getting lines at database...\n";
            $lineCodes = $database->getLineCodes();
            echo "Searching lines at API by number...\n";
            for($i = 1; $i <= 9; $i++){
                $lines = $this->searchLines($i);
                $lines = json_decode($lines['body']);
                foreach ($lines as $key => $line) {
                    if(!in_array($line->cl, $lineCodes)){
                        echo "Saving line ".$line->cl."...\n";
                        $database->saveLine($line);
                        array_push($lineCodes, $line->cl);
                        echo "Saved...\n";
                    }else{
                        echo "Line ".$line->cl." already saved!\n";
                    }
                }
            }
            echo "Searching lines at API by letters...\n";
            for($i = 0;$i < 26; $i++){
                $lines = $this->searchLines(chr(97+$i));
                $lines = json_decode($lines['body']);
                foreach ($lines as $key => $line) {
                    if(!in_array($line->cl, $lineCodes)){
                        echo "Saving line ".$line->cl."...\n";
                        $database->saveLine($line);
                        array_push($lineCodes, $line->cl);
                        echo "Saved...\n";
                    }else{
                        echo "Line ".$line->cl." already saved!\n";
                    }
                }
            }
        }else{
            echo "Not Autorized\n";
        }
    }

    public function getPositions(){
        if($this->auth()){
            echo "Autorized\n";
            $database = new database();
            echo "Getting lines at database...\n";
            $lineCodes = $database->getLineCodes();
            foreach ($lineCodes as $lineCode) {
                echo "Getting bus positions of line ".$lineCode."\n";
                $positions = $this->getLinePositions($lineCode);
                $positions = json_decode($positions);
                if(count($positions->vs) == 0){
                    echo "No positions for this line right now!\n";
                } else {
                    $database->savePositions($positions, $lineCode);
                    echo "Positions saved\n";
                }
            }
        }else{
            echo "Not Autorized!\n";
        }
    }

}

?>
