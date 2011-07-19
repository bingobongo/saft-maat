<?php

namespace Saft;


Class Auth extends Copilot {


	public function __construct(){
		parent::__construct();
	}


	# @param	string

	protected function __auth($spell){
		require_once('httpdigest.php');
		$headerAuth = $this->__getHeader('Authorization');

		# missing Authorization header
		if ($headerAuth === 0){
			# check for "auth" request (JavaScript), send http authentication
			#    header if not already sent or output login form on first load
			if (isset($_REQUEST['auth']) === true)
				Httpdigest::sendAuthHeader();
			else
				return Maat::$client = 2;

			exit;
		}

		$name = Httpdigest::authenticate($headerAuth, $spell);
		# log in/out, usually asynchronous
		if (isset($_REQUEST['auth']) === true){

			# failed login: wrong credentials; log and slow down;
			#    send Bad Request to stop client from asking for details;
			#    let JavaScript part on client-side work once again
			if ($name === 0){
				$this->__log('err.auth');
				Elf::sendHttpHeader(400);

				# force client to reset after failed login (Firefox 4)
				if (strpos(Maat::$client, 'firefox') === 0)
					header('X-Maat-Reset: true');

			# reset: let the JavaScript part on client-side work once again;
			#    slow down, feign busyness
			} else if ($name === 'null'){
				sleep(min(1, max(1, (ini_get('max_execution_time') / 2))));
				Elf::sendHttpHeader(400);

			# successful logout: client cables "quit" as username
			#    (JavaScript, URI should end with former "username/")
			#    send Bad Request to stop client from asking for details;
			#    let JavaScript part on client-side work once again
			} else if ($name === 'quit'){
				$this->__log('deauth');
				Elf::sendHttpHeader(400);

			# reboot/reauth required,
			#    indicate the required reboot on client-side
			} else if ($name > 2){
				Elf::sendExitHeader(200, 'text/plain');
				echo Maat::$lang['error']['reboot'] , ' -' , $name , '';

			# successful login
			} else
				Elf::sendHttpHeader(200);

			# stop
			exit;

		# standard requests
		} else {

			# not authenticated yet, force output of login form after successful logout
			if (	$name === 0
				or	$name === 'null'
				or	$name === 'quit'
			)
				return Maat::$client = 2;

			# usually the authentication lifespan may have expired or there is a
			#    request count mismatch; or author has logged in with different
			#    clients simultaneously; or man-in-the-middle attack running;
			#    for details dive into /propeller/maat/elves/httpdigest.php
			else if ($name > 2){

				# presumably the users authentication lifespan expired
				if (isset($_REQUEST['entry']) === true){

					if (ob_get_length() !== false)
						ob_clean();

					Elf::sendExitHeader(200, 'text/plain');

					if (	isset($_REQUEST['from']) === true
						&&	$_REQUEST['from'] === 'publish-entry'
					)
						echo Maat::$lang['confirm']['expired_unsaved'];

					else
						echo Maat::$lang['confirm']['expired'];

					# stop
					exit;

				# or simply no more signed in: reauth/reboot!
				} else
					return Maat::$client = $name;

			# begin configuration
			} else {
				unset($headerAuth, $name, $spell);
				return 1;
			}
		}
	}


	# @param	string
	# @return	string or integer

	protected function __getHeader($key){

		if (isset($_SERVER[$key]) === true)
			return $_SERVER[$key];

		else if (	$key === 'Authorization'
				&&	isset($_SERVER['PHP_AUTH_DIGEST']) === true
		)
			return $_SERVER['PHP_AUTH_DIGEST'];

		else if ($headers = $this->__getHeaders())
			return isset($headers[$key])
				? $headers[$key]
				: 0;
		else
			return 0;
	}


	# @return	array or integer
	#
	#			grab response and request headers from Apache;
	#			i.e. for Authorization header, if not forwarded automatically

	protected function __getHeaders(){

		if (	function_exists('apache_response_headers')
			&&	function_exists('apache_request_headers')
		){
			$headersRes = apache_response_headers();
			$headersReq = apache_request_headers();
			return array_merge($headersRes, $headersReq);

		} else
			return 0;
	}


	# @param	string

	private function __log($str){

		if (strpos($str, 'err.') === 0){
			$path = Maat::$logRoot . '/' . $str . '.log';
			# slow down, protect from brute-force-attacks, info leakage
			if ($str === 'err.auth')
				sleep(min(3, max(3, (ini_get('max_execution_time') / 2))));

		# slow down, feign busyness
		} else if ($str === 'deauth'){
			sleep(min(1, max(1, (ini_get('max_execution_time') / 2))));
			$path = Maat::$logRoot . '/' . App::$author . '/' . $str . '.log';
		}

		Elf::writeToFile($path, $this->__getLogStr());
	}


	private function __getLogStr(){
		return	date('Ymd-H.i.s O')
				# cut "<!-- " and " -->" off
			.	' ' . substr(Elf::getSqueezedStr(), 5, -4)
			.	' ' . $_SERVER['REMOTE_ADDR']
			.	' ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			.	"\n";
	}

}
