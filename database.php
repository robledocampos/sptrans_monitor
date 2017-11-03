<?php

class database{
    private $host = "localhost";
    private $user = "root";
    private $pass = "drogo27";
    private $database = "sptrans_monitor";

    public function __construct(){

    }

    private function connect(){
        $mysqli = new mysqli($this->host, $this->user, $this->pass, $this->database);
        if ($mysqli->connect_error) {
            die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
        }
        return $mysqli;
    }

    private function close($mysqli){
        $mysqli->close();
    }

    public function getLineCodes(){
        $mysqli = $this->connect();
        $sql = "SELECT codigo_linha FROM bus_lines";
        $result = $mysqli->query($sql);
        $lineCodes = array();
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            array_push($lineCodes, $row['codigo_linha']);
        }
        $this->close($mysqli);
        return $lineCodes;
    }

    public function saveLine($line){
        $mysqli = $this->connect();
        $sql = "INSERT INTO bus_lines (codigo_linha, circular, letreiro, sentido, tipo, denominacao_tpts, denominacao_tstp, info)
            VALUES(".$line->CodigoLinha.",".$line->Circular.",'".$line->Letreiro."',".$line->Sentido.",'".$line->Tipo."','".
            utf8_decode($line->DenominacaoTPTS)."','".utf8_decode($line->DenominacaoTSTP)."','".$line->Informacoes."')";
        $result = $mysqli->query($sql);
        $this->close($mysqli);
        return $result;
    }

    public function savePositions($positions, $lineCode){
        date_default_timezone_set("America/Sao_Paulo");
        $mysqli = $this->connect();
        $sql = "INSERT INTO bus_positions (codigo_linha, p, a, py, px, dia_hora) VALUES";
        $dayHour = date("Y-m-d ") . $positions->hr.":00";
        foreach($positions->vs as $key => $position){
            $lastChar = (count($positions->vs)-1 == $key) ? ';' : ',';
            $sql .= "(".$lineCode.",'".$position->p."',".$position->a.",".$position->py.",".
                $position->px.",'".$dayHour."')".$lastChar;
        }
        $result = $mysqli->query($sql);
        $this->close($mysqli);
        return $result;
    }

    public function getPositions($hours, $timeSlice){
        date_default_timezone_set("America/Sao_Paulo");
        $now = date("Y-m-d H:i:s");
        $mysqli = $this->connect();
        $sql = "SELECT dia_hora, py, px FROM bus_positions WHERE dia_hora BETWEEN DATE_SUB('".$now."', INTERVAL ".$hours." ".$timeSlice.") AND '". $now ."'";
        $result = $mysqli->query($sql);
        $positions = array();
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            array_push($positions, $row);
        }
        $this->close($mysqli);
        return $positions;
    }

    public function getLastCollectTime(){
        $mysqli = $this->connect();
        $sql = "SELECT dia_hora FROM bus_positions ORDER BY id DESC LIMIT 1";
        $result = $mysqli->query($sql);
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this->close($mysqli);
        return $row['dia_hora'];
    }

    public function getCurrentPositions(){
        $lastCollectTime = $this->getLastCollectTime();
        $mysqli = $this->connect();
        $sql = "SELECT dia_hora, py, px FROM bus_positions WHERE dia_hora LIKE '".$lastCollectTime."'";
        $result = $mysqli->query($sql);
        $positions = array();
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            array_push($positions, $row);
        }
        $this->close($mysqli);
        return $positions;
    }

}
