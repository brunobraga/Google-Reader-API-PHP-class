<?php
/**
*	@author Dimmduh
*	@email dimmduh@gmail.com
*/
class GoogleReaderAPI{
	private $service;
	private $auth = '';
	private $source = '314ple-GoogleReaderAPIClass-0.1';
	private $accountType = 'HOSTED_OR_GOOGLE';
	private $clientlogin_url = 'https://www.google.com/accounts/ClientLogin';
	private $session_var_auth_name = 'google_auth';
	
	function __construct( $email, $password, $service = 'reader' ){
		if (isset( $service ) ){
			$this -> service = $service;
		}
		$this -> clientLogin( $email, $password );
	}
	
	private function request( $url, $headers, $post_fields ){
	
		$curl = curl_init( $url );
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		if ( is_array( $header ) ){
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		if ( is_array( $post_fields ) ){
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
		}
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$response = array();
		$response['text'] = curl_exec($curl);
		$response['info'] = curl_getinfo( $curl);
		$response['code'] = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		
		curl_close( $curl );
		return $response;
	}
	private function clientLogin( $email, $password ){
		
		$response = $this -> request( $this -> clientlogin_url, false, array(
			"accountType" => $this -> accountType,
			"Email" => $email,
			"Passwd" => $password,
			"service" => $this -> service,
			"source" => $this -> source,
		));
				
		if ( $response['code'] == 200) {
			preg_match("/Auth=([a-z0-9_\-]+)/i", $response['text'], $matches);
			if ($matches[1]){
				$this -> auth = $matches[1];
				$_SESSION[ $this -> session_var_auth_name ] = $this -> auth;
				return true;
			} else {
				Throw new AutentificationException('Auth error: not finded Auth token');
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