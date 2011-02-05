<?php
/**
*	@author Dimmduh
*	@email dimmduh@gmail.com
*/
class GoogleReaderAPI{
	private $service;
	private $auth;
	private $source = '314ple-GoogleReaderAPIClass-0.1';
	private $accountType = 'HOSTED_OR_GOOGLE';
	private $clientlogin_url = 'https://www.google.com/accounts/ClientLogin';
	private $session_var_auth_name = 'google_auth';
	
	function __construct( $email, $password, $service = 'reader' ){
		if (isset( $service ) ){
			$this -> service = $service;
		}
 		if ( isset($_SESSION[ $this -> session_var_auth_name ] ) ){
			$this -> auth = $_SESSION[ $this -> session_var_auth_name ];
			//echo "Loading";
		} else {
			//echo "create new";
			$this -> clientLogin( $email, $password );
		}
	}
	
	private function request( $url, $type = 'get', $headers = false, $fields = false, $cookie = false){
	
		$curl = curl_init();
		
		if ( $fields ){
			if ($type == 'get'){
				$url .= '?'.http_build_query( $fields );
			} else {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
			}
		}
		if ( $headers ){
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
 		if ($cookie){
			curl_setopt($curl, CURLOPT_COOKIE, $cookie);
		}
		
		curl_setopt($curl, CURLOPT_URL, $url);
		if (strpos($url, 'https://') !== false){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		
		$response = array();
		$response['text'] = curl_exec($curl);
		$response['info'] = curl_getinfo( $curl);
		$response['code'] = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		$response['body'] = substr( $response['text'], $response['info']['header_size'] );
		
		//print_r( $response );
		curl_close( $curl );
		return $response;
	}

	private function request2google( $url, $type, $headers = false, $fields = false ){
		if ( $this -> auth ){
			$headers[] = 'Content-type: application/x-www-form-urlencoded';
			$headers[] = 'Authorization: GoogleLogin auth='.$this -> auth;
			
			$response = $this -> request( $url, $type, $headers, $fields);
			if ( $response['code'] == 200 ){
				if ( isset( $fields['output'] ) ){
					switch ($fields['output']){
						case 'json':
							return json_decode( $response['body'] );
							break;
						case 'xml':
							return (new SimpleXMLElement( $response['body'] ) );
							break;
					}
				} else {
					return $response['body'];
				}
			} else {
				Throw new AutentificationException('Auth error: server response '.$response['code'] );
			}
			
		} else {
			Throw new AutentificationException('Auth error: not finded Auth token');
		}
	}
	
	public function get_subscription_list(){
		return $this -> request2google('https://www.google.com/reader/api/0/subscription/list', "get", false, array(
				'output' => 'json',
				'ck' => time(),
				'client' => 'scroll',
			));
		//return $this -> request2google('http://www.google.com/reader/api/0/user-info');
		//return $this -> request2google('https://www.google.com/reader/api/0/unread-count');
	}
	
	private function clientLogin( $email, $password ){
		
		$response = $this -> request( $this -> clientlogin_url, 'post', false, array(
			"accountType" => $this -> accountType,
			"Email" => $email,
			"Passwd" => $password,
			"service" => $this -> service,
			"source" => $this -> source,
		));
				
		if ( $response['code'] == 200) {
			preg_match("/Auth=([a-z0-9_\-]+)/i", $response['body'], $matches_auth);			
			if ($matches_auth[1]){
				$this -> auth = $matches_auth[1];
				$_SESSION[ $this -> session_var_auth_name ] = $this -> auth;
				return true;
			} else {
				Throw new AutentificationException('Auth error: not finded Auth token in response');
				return false;
			}
		} else {
			Throw new AutentificationException('Auth error: server response '.$response['code'] );
			return false;
		}
	}

}

//Exceptions
class AutentificationException extends Exception{

}

?>