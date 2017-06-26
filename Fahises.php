<?
/* API Fahises PHP
 * Version: 0.1
 * PHP 5.0.2, no anonymous functions and CURL.
 *
 * For now, only need token and id of Spot/Place.
 * Endpoint: https://app.fahises.com.br/rest
 * Method: GET,POST,PUT
 *
 * Functions: ->Read, ->Write, ->Tables
 * Usage:
 * $F = new Fahises(<ID>,<TOKEN>);
 *
 * $F->Read(<nome,topic,options,history,online>);
 * $F->Write(<nome,topic,iframe,token>);
 * $F->Tables(<add,rem,clear>,<allow,deny>,<uid>);
 *
*/
 
class Fahises { 
	private $token;
	private $id;
	private $result;
	private $last;
	private $silent = false;

	private function error(){
		$this->result = false;
		$args = func_get_args();
		if($this->silent) return $args;

		switch($args[0]){
			case 001:
				echo "Insufficient number of arguments.\n";
				break;
			case 002:
				echo "There is already an item, use ->Drop().\n";
				break;
			case 003:
				echo "There was some error with the connection.\n";
				break;
			case 004:
				echo "There is an error with Token/ID.\n";
				break;
			case 005:
				echo "There is no connection to the Spot/Place.\n";
				break;
			default:
				print_r($args);
				break;
		}
	}
	public function __construct() {
		$args = func_get_args();
		if(sizeof($args) < 2) return $this->error(001);
		$this->id = $args[0];
		$this->token = $args[1];
		$this->silent = $args[2];

		return $this->Connect();

	}
	public function Connect(){
		$args = func_get_args();

		if(func_num_args() == 2){
			$this->id = $args[0];
			$this->token = $args[1];
		}

		if($this->result && !$args[0]) return $this->error(002);
		$data = array('token' => $this->token,'id' => $this->id);

		$opts = array('http' => array('method'  => 'POST', 'header'  => 'Content-type: application/x-www-form-urlencoded', 'content' => http_build_query($data) ));

		$context  = @stream_context_create($opts);
		$this->result = json_decode(@file_get_contents('https://app.fahises.com.br/rest', false, $context));
		$this->last = microtime(true);

		if(!$this->result) return $this->error(003);
		if($this->result->error) return $this->error($this->result->msg);

		return true;

	}
	public function Drop(){
		$this->result = false;
	}
	public function Write(){
		$args = func_get_args();

		if(!$this->result) return $this->error(005);
		if(!sizeof($args)) return $this->error(001);

		$data = array('token' => $this->token,'id' => $this->id);

		$data["submit"] = json_encode($args);
		$data = http_build_query($data);
		$length = strlen($data);
		$opts = array('http' => array('method'  => 'PUT', 'header'  => "Content-Length: $length\r\nContent-Type: application/x-www-form-urlencoded", 'content' => $data ));

		if(microtime(true) - $this->last < 1) sleep(1); 	
		$context  = @stream_context_create($opts);
		$result = @file_get_contents('https://app.fahises.com.br/rest', false, $context);
		$this->last = microtime(true);

		if(!$result) return $this->error(003);
		if($result->error) return $this->error($this->result->msg);

		return $result;

	}
	public function Read(){
		$args = func_get_args();

		if(!$this->result) return $this->error(005);

		if(!sizeof($args)) return $this->result;

		$x = array();
		foreach ($args as &$v) {
			if($this->result->$v) $x[] = $this->result->$v;
		}

		return $x;
	}
	public function Tables($type,$chain,$uid){
		return $this->Write(array("tables"=>array($type=>json_encode(array("chain"=>"$chain","uid"=>"$uid")))));
	}
} 
?>
