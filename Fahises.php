<?
/* API Fahises PHP
 * 0.1 Initial.
 * 0.2 04/08/2017
 * * Added ->Validate to check if an UID is it validate after logged.
 * 0.3 18/08/2017
 * * Added ->Likes for USERID or UID. Only available for users/products added for specific uses.
 *
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
 * $F->Read(<nome,topic,options,history,online,history>);
 * $F->Write(<nome,topic,iframe,background,token>,<VALUE>);
 * $F->Tables(<add,rem,clear>,<allow,deny>,<uid>);
 *
 * $F->Validate(<uid>,<token>);
*/
 
class Fahises { 
	private $token;
	private $id;
	private $result;
	private $last;
	private $silent = false;

	private function find(){
		$args = func_get_args();

		if(!$args[0] || !is_array($args[0]) || !$args[1]) return false;

		for($i=0;$i<sizeof($args[0]);$i++){
			$element = $args[0][$i];
			if(!$args[2] && $element->$args[1]) return $element;
			else if ($args[2] == $element->$args[1]) return $args[3] ? $i : $element; 
		}
		return false;
	}
	private function error(){
		$this->result = false;
		$args = func_get_args();
		if($this->silent) return $args;

		switch($args[0]){
			case 001:
				echo "Número insuficiente de argumentos\n";
				break;
			case 002:
				echo "Já há um item, use ->DROP()\n";
				break;
			case 003:
				echo "Houve algum erro com a conexão.\n";
				break;
			case 004:
				echo "Erro com o Token/ID\n";
				break;
			case 005:
				echo "Não há conexão com o Spot/Place\n";
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
	public function Validate($uid,$token){
		if(!$this->result->online || !sizeof($this->result->online)) return false;

		$opts = array('http' => array('method'  => 'GET', 'header'  => 'Content-type: application/x-www-form-urlencoded' ));

		$context  = @stream_context_create($opts);
		return $this->find($this->result->online,"uid",$uid) && json_decode(@file_get_contents("https://app.fahises.com.br/api/I$uid/$token", false, $context))->ok ? true : false;
	}
	public function Likes($userid){
		if(!$userid) return false;
		if(microtime(true) - $this->last < 1) sleep(1); 	
		$data = array('token' => $this->token,'id' => $this->id,'userid' => $userid);

		$opts = array('http' => array('method'  => 'POST', 'header'  => 'Content-type: application/x-www-form-urlencoded', 'content' => http_build_query($data) ));

		$context = @stream_context_create($opts);
		$this->last = microtime(true);
		return json_decode(@file_get_contents("https://app.fahises.com.br/rest", false, $context));
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
