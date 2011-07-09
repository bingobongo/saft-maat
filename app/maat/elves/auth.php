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

		if ($headerAuth === 0){							# missing Authorization header

			if (isset($_REQUEST['auth']) === true)			# check for “auth” request (JavaScript) and
				Httpdigest::sendAuthHeader();				#    send http authentication header if not already sent or
			else											#    output login form on first page load
				return Maat::$client = 2;

			exit;
		}

		$name = Httpdigest::authenticate($headerAuth, $spell);

		if (isset($_REQUEST['auth']) === true){			# log in/out request, usually asynchronous

			if ($name === 0){								# failed login: wrong credentials; log and slow down;
				$this->__log('err.auth');					#    send Bad Request to stop client from asking for details;
				Elf::sendHttpHeader(400);					#    let the JavaScript part on client-side work once again

				if (strpos(Maat::$client, 'firefox') === 0)	# forces client to reset after failed login (Firefox 4)
					header('X-Maat-Reset: true');

			} else if ($name === 'null'){					# reset: let the JavaScript part on client-side work once again
				sleep(min(1, max(1, (ini_get('max_execution_time') / 2))));
				Elf::sendHttpHeader(400);					#    slow down, feels as is a lot going on behind the curtain

			} else if ($name === 'quit'){					# successful logout: client will cable “quit” as username
				$this->__log('deauth');						#    (JavaScript, URI should end with former “username/”)
				Elf::sendHttpHeader(400);					#    send Bad Request to stop client from asking for details;
															#    let the JavaScript part on client-side work once again

			} else if ($name > 2){							# reboot/reauth required
				Elf::sendExitHeader(200, 'text/plain');		#    will indicate the required reboot on client-side
				echo Maat::$lang['error']['reboot'] , ' -' , $name , '';

			} else											# successful login
				Elf::sendHttpHeader(200);

			exit;											# stop

		} else {										# standard requests

			if (	$name === 0								# not authenticated yet, force output of
				or	$name === 'null'						#    the login form after successful logout
				or	$name === 'quit'
			)
				return Maat::$client = 2;
															# usually the authentication lifespan may have expired or
															#    there’s a request count mismatch; or an author may have
															#    logged in with different clients simultaneously;
			else if ($name > 2){							#    or a man-in-the-middle attack is running; for details
															#    dive into “/propeller/maat/elves/httpdigest.php”

				if (isset($_REQUEST['entry']) === true){		# presumably the users’s authentication lifespan expired …

					if (ob_get_length() !== false)
						ob_clean();

					Elf::sendExitHeader(200, 'text/plain');

					if (	isset($_REQUEST['from']) === true
						&&	$_REQUEST['from'] === 'publish-entry'
					)
						echo Maat::$lang['confirm']['expired_unsaved'];

					else
						echo Maat::$lang['confirm']['expired'];

					exit;										#    stop

				} else											# … or simply no more signed in: reauth/reboot!
					return Maat::$client = $name;

			} else {										# begin configuration …
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


	# @return	array or integer	grab response and request headers from Apache;
	#								i.e. for “Authorization” header, if not forwarded automatically

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
														# slow down, try to protect the machine from
			if ($str === 'err.auth')					#    brute-force-attacks, information leakage etc.
				sleep(min(3, max(3, (ini_get('max_execution_time') / 2))));

		} else if ($str === 'deauth'){					# slow down, feels as is a lot going on behind the curtain
			sleep(min(1, max(1, (ini_get('max_execution_time') / 2))));
			$path = Maat::$logRoot . '/' . App::$author . '/' . $str . '.log';
		}

		Elf::writeToFile($path, $this->__getLogStr());
	}


	private function __getLogStr(){
		return	date('Ymd-H.i.s O')						# cut “<!-- ” and “ -->” off
			.	' ' . substr(Elf::getSqueezedStr(), 5, -4)
			.	' ' . $_SERVER['REMOTE_ADDR']
			.	' ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			.	"\n";
	}

}
