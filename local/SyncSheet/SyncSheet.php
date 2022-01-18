<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once(dirname(__FILE__).'/../../config.php');



  
class SyncSheet{

    private $idAttendance;

    function __construct($idAttendance) {
        $this->idAttendance = $idAttendance;
    }

    public function getSheet($spreadsheetId){

        $client = new Google_Client();
		putenv('HTTPS_PROXY=localhost:3128');

        $client->setApplicationName('Google Sheets and PHP');

        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);


        $client->setAccessType('offline');

        $client->setAuthConfig(__DIR__ . '/credentials.json');


        $service = new Google_Service_Sheets($client);
        //O id atual 11OUkfHXXdPuL-N2vIs1HweDdXUoBmNXwryPGpaIDVj0
 

        $get_range = "A5:ZZ1000";


        //Request to get data from spreadsheet.

        $response = $service->spreadsheets_values->get($spreadsheetId, $get_range);

        $values = $response->getValues();

        return $values;

    }

    // d/m/Y
    private function obtemSessionID($data) {

        $id = 0;
        global $DB;
        $sql = "select id from {attendance_sessions} 
                    where 
                        to_char(to_timestamp(sessdate), 'DD/MM/YYYY') = ?";
        $id = $DB->get_record_sql($sql,[$data]);
        // SELECT id
        // FROM attendance_sessions
        // WHERE to_char(to_timestamp(sessdate), 'DD/MM/YYYY') = $data;
        return $id->id;
    }       
    
    protected function obtemIDUsuarioDoCPF($cpfAluno) {

        $id = 0;
        global $DB;
        $sql = "select id from {user}
                    where
                        username = ?";
        $id = $DB->get_record_sql($sql,[$cpfAluno]);

        // SELECT id
        // FROM {{user}}
        // WHERE username = $cpfAluno;

        return $id->id;
    }       

    /**
     * valorPresenca é P ou A
     */
    private function obtemIDStatusPresenca($valorPresenca) {

        $id = 0;
        global $DB;
        $sql = "select id from {attendance_statuses}
                    where acronym = ?
                        and attendanceid = ?";
        
        $id = $DB->get_record_sql($sql,[$valorPresenca,$this->idAttendance]);
        
        // SELECT id
        // FROM {{attencance_statuses}}
        // WHERE acronym = $valorPresenca
        //      AND attendanceid = $this->idAttendance;
        //TODO RETORNA ARRAY DE OBJETOS
        return  $id->id;
    }     


    public function insertSheet($values){
        global $DB;

        $QuantLinhas = count($values);
        $QuantColunas = count($values[1]);

        $id_presenca = 1;
        $id_falta = 2;

        for($i = 3; $i < $QuantColunas - 2; $i++ ){
            
            $data = $values[0][$i];
            $dataTimestamp = (DateTime::createFromFormat('d/m/Y', $data))->getTimestamp();
            //echo "Processando $data timestamp $dataTimestamp";
            //echo"<p></p>";
            $idSession = $this->obtemSessionID($data);
            $currentTimestamp = (new Datetime())->format('U');

            //echo "O id da sessão é $idSession XXXXXX";
            //echo"<p></p>";


            //if($values[0][$i] == $/*retorno da query session*/ ){
            for($j = 1; $j < $QuantLinhas ; $j++){
                $cpfAluno = $values[$j][1]; // hoje é matrícula
                $idAluno = $this->obtemIDUsuarioDoCPF($cpfAluno);
                $valorPresenca = $values[$j][$i];
                //echo "Presença de $idAluno é $valorPresenca";
                //echo"<p></p>";
                $idStatusMapeado = $this->obtemIDStatusPresenca($valorPresenca);
                //echo "Status da precença é $idStatusMapeado";
                //echo"<p></p>";
		$DB -> execute(
			"DELETE from  {attendance_log}
				where sessionid = ? and studentid = ? and statusid = ? and takenby = ?",
                            	[$idSession,$idAluno,$idStatusMapeado,'3']
				
		);
                $DB -> execute(
                        "insert into {attendance_log}(sessionid, studentid, statusid,timetaken, takenby) 
                            values (?,?,?,?,?)", 
                            [$idSession,$idAluno,$idStatusMapeado,$currentTimestamp,'3']
                );
            }
            //echo "Gravou";
            //echo"<p></p>";
        };

    }

}
class ScoreSync extends SyncSheet{

    function __construct($idCourse) {
        $this->idCourse = $idCourse;
    }

    private function obtemItemIdDaProva(){
        global $DB;
        $sql = "select id from {grade_items}
        where courseid = ?
            and idnumber = ?";

        $id = $DB->get_record_sql($sql,[$this->idCourse,'P']);
        return $id->id;
    }
	

	public function insertSheet($values){
		global $DB;
		$QuantLinhas = count($values);
		$QuantColunas = count($values[1]);
        //print_r($values[0][$QuantColunas-1]);
		for( $i = 1; $i < $QuantLinhas; $i++){
			
			$cpfAluno = $values[$i][1];
			$idAluno = $this->obtemIDUsuarioDoCPF($cpfAluno);
            $notaAluno = $values[$i][$QuantColunas-1];
            $idItem = $this->obtemItemIdDaProva();
            $currentTimestamp = (new Datetime())->format('U');
            
            $DB -> execute(
                "UPDATE {grade_grades} SET (finalgrade,usermodified,timemodified) = (?,?,?)
                    where itemid = ? and userid = ?",
                [$notaAluno,'3',$currentTimestamp,$idItem,$idAluno]
            );
		}
		
	}
}

