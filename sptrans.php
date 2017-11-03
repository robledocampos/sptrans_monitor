<?php

require_once("database.php");

class sptrans{
    private $apiUrl = "http://api.olhovivo.sptrans.com.br/v0/";
    private $token = "13b402205e1d4578434ea3b9c4599651f769b7d9f8aa6e8257de21b74efd17a7";
    private $debug = 0;
    private $credentials = null;

    public function auth(){
        $request = array(
            "token" => $this->token
        );
        $result = $this->makeCurl($this->apiUrl, $request, "Login/Autenticar/", true);
        return $result;
    }

    public function searchLines($query){
        $request = array(
            "termosBusca" => $query
        );
        $result = $this->makeCurl($this->apiUrl, $request, "Linha/Buscar/", false);
        return $result;
    }

    public function getLinePositions($lineCode){
        $request = array(
            "codigoLinha" => $lineCode
        );
        $result = $this->makeCurl($this->apiUrl, $request, "Posicao/", false);
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
                $lines = json_decode($lines);
                foreach ($lines as $key => $line) {
                    if(!in_array($line->CodigoLinha, $lineCodes)){
                        echo "Saving line ".$line->CodigoLinha."...\n";
                        $database->saveLine($line);
                        array_push($lineCodes, $line->CodigoLinha);
                        echo "Saved...\n";
                    }else{
                        echo "Line ".$line->CodigoLinha." already saved!\n";
                    }
                }
            }
            echo "Searching lines at API by letters...\n";
            for($i = 0;$i < 26; $i++){
                $lines = $this->searchLines(chr(97+$i));
                $lines = json_decode($lines);
                foreach ($lines as $key => $line) {
                    if(!in_array($line->CodigoLinha, $lineCodes)){
                        echo "Saving line ".$line->CodigoLinha."...\n";
                        $database->saveLine($line);
                        array_push($lineCodes, $line->CodigoLinha);
                        echo "Saved...\n";
                    }else{
                        echo "Line ".$line->CodigoLinha." already saved!\n";
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

    public function makeCurl($api_url, $request, $action, $type){
        $request = http_build_query($request);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url . $action."?".$request);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if($this->credentials){
            curl_setopt($ch, CURLOPT_COOKIE, "apiCredentials=".$this->credentials);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Content-Length: 0'
            )
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, $type);
        if ($this->debug) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
        }
        $result = curl_exec($ch);
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        if(isset($cookies['apiCredentials'])){
            $this->credentials = $cookies['apiCredentials'];
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $body = substr($result, $header_size);
        curl_close($ch);
        return $body;
    }

}

?>
