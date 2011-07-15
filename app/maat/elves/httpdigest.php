<?php

namespace Saft;


Class Httpdigest {


	# Httpdigest
	#
	# http://en.wikipedia.org/wiki/Digest_access_authentication
	# inspired, partially based on and kudos to Paul James
	#    HTTP Authentication with HTML Forms,
	#        http://www.peej.co.uk/articles/http-auth-with-html-forms.html, and
	#    PHP HTTP Digest, http://www.peej.co.uk/projects/phphttpdigest.html


	# @param	string
	# @param	string
	# @return	string or integer
	#
	#			0 = failed;
	#			3 = expired or request count mismatch (reboot);
	#			4 = kicked out,
	#			    same user logged in with different clients simultaneously;
	#			5 = something else faulty (error, reboot);
	#			null as string = logout; similar to "quit",
	#			    but without writing to any log;
	#			quit as string = logout;
	#			everything else will be just fine

	public static function authenticate(&$headerAuth, &$spell){

		if (	preg_match('/username="([^"]+)"/', $headerAuth, $name)
			&&	preg_match('/nonce="([^"]+)"/', $headerAuth, $nonce)
			&&	preg_match('/response="([^"]+)"/', $headerAuth, $response)
			&&	preg_match('/opaque="([^"]+)"/', $headerAuth, $opaque)
			&&	preg_match('/uri="([^"]+)"/', $headerAuth, $URI)

		){
			# the author name may not have any special characters

			if (preg_match('{^[\w-]+$}i', $name[1]) === 0)
				return 0;

			$name = strtolower($name[1]);
			$nonce = $nonce[1];
			$path = Maat::$logRoot . '/' . App::$author;

			# BEWARE: waive request count reset when possible!
			#    reset it to 0 on logout when browser is Chrome, because
			#    Chrome will reset it after logout; though Firefox will not,
			#    reset it all the same, because of rare edge cases
			#    (client will cable "quit" for logout, "null" for reset only)

			if (	$name === 'null'
				xor	$name === 'quit'
			){								# start request count reset

				$path.= '/' . 'auth.log';
				$content = is_readable($path) === true
					? file_get_contents($path)
					: '';

				clearstatcache();

				$line = substr($content, 0, strpos($content, "\n"));
				$offset = strpos($line, $nonce);
				$nonceOK = $nonce === self::__getNonce();

				if (	$offset !== false
					&&	$nonceOK === true
				){
					$line = substr($line, strlen($nonce) + 1);
					$line = substr($line, strpos($line, '%'));
					$content = $nonce . '%0' . $line . "\n" . substr($content, strpos($content, "\n") + 1);
					self::__logNonce($path, $content);
				}

				return $name;				# allow logout log, but no reset log
			}
											# make sure that name in the header
			if (	$name === App::$author	#    and in the URI are equal
				&&	$opaque[1] === self::__getOpaque()
				&&	$URI[1] === $_SERVER['REQUEST_URI']
				&&	preg_match('/qop="?([^,\s"]+)/', $headerAuth, $qualityOfProtectionCode)
				&&	preg_match('/nc=([^,\s"]+)/', $headerAuth, $requestCount)
				&&	preg_match('/cnonce="([^"]+)"/', $headerAuth, $clientNonce)
			){
				$a1 = md5($name . ':' . self::__getRealm() . ':' . $spell);
				$a2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $_SERVER['REQUEST_URI']);
				$expectedResponse = md5($a1 . ':' . $nonce . ':' . $requestCount[1]
					. ':' . $clientNonce[1] . ':' . $qualityOfProtectionCode[1] . ':' . $a2);
				$requestCount = hexdec($requestCount[1]);

											# start nonce log
				if ($response[1] === $expectedResponse){
					Elf::makeDirOnDemand($path, App::$perms['cache']);
					$path.= '/' . 'auth.log';
					$content = is_readable($path) === true
						? file_get_contents($path)
						: '';

					clearstatcache();

					$offset = strpos($content, $nonce);
					$time = $_SERVER['REQUEST_TIME'];
					$nonceOK = $nonce === self::__getNonce();

					if ($offset !== false){

						# new nonce always adds on top; therefore, a valid
						#    nonce may be on the very first line only

						if ($offset === 0){

							#    , nonce omitted
							# $n = request count
							# $c = orignal birth time of nonce
							#      (= user session start time)
							# $e = expiration time of nonce
							list(, $n, $c, $e) = explode('%', substr($content, 0, strpos($content, "\n")));

							# make sure that current request count is greater
							#    than the logged one, at least +1 (less strict
							#    this would be "intval($n) < $requestCount");
							#    session may exceed lifespan eightfold;
							#    after that force to log in once again

							if (	1 + intval($n) <= $requestCount
								&&	(Maat::LIFESPAN * 8 + $c) > $time
								&&	$e > $time
							){
								# update request count, extend expiry

								if ($nonceOK === true){
									$content = $nonce . '%' . strval($requestCount) . '%' . $c
										. '%' . ($time + Maat::LIFESPAN) . "\n"
										. substr($content, strpos($content, "\n") + 1);
									self::__logNonce($path, $content);

								# stale: client should re-send with new nonce
								#    provided; log that nonce with request count 0

								} else
									exit(self::sendAuthHeader($path, $c, $content));

							# authentication lifespan expired or
							#    request count mismatch occured => reboot!

							} else
								$name = 3;

						# not latest nonce, presumably man-in-the-middle attack;
						#    or user logged in simultaneously with multiple
						#    clients, will kick the last one out => reboot!

						} else
							$name = 4;

					# user probably logged in just before, log new nonce

					} else if ($nonceOK === true){
						$content = $nonce . '%' . $requestCount . '%' . $time
							. '%' . ($time + Maat::LIFESPAN) . "\n" . $content;
						self::__logNonce($path, $content);

					# or there is simply something else faulty => reboot!

					} else
						$name = 5;

					return $name;
				}
			}
		}

		return 0;
	}


	# @param	string
	# @param	string or integer
	# @param	string

	public static function sendAuthHeader($path = null, $userSessionStartTime = null, $content = null){
		$nonce = self::__getNonce();

		if (	$path !== null
			&&	$userSessionStartTime !== null
			&&	$content !== null
		){
			$stale = '", stale=true';
			$content = $nonce . '%0%' . $userSessionStartTime
				. '%' . ($_SERVER['REQUEST_TIME'] + Maat::LIFESPAN) . "\n" . $content;
			self::__logNonce($path, $content);

		} else
			$stale = '"';


		# 401 aims to suppress native http auth box of the client

		Elf::sendHttpHeader(401);
		header('WWW-Authenticate: Digest realm="' . self::__getRealm()
			. '", domain="' . App::$absolute . App::$author
			. '/", qop=auth, algorithm=MD5, nonce="' . $nonce
			. '", opaque="' . self::__getOpaque() . $stale
		);
	}


	# @return	string	generate realm

	private function __getRealm(){			# kingly name of the app or similar
		return hash('ripemd160', base64_encode('saftmaat@' . Maat::$domainID));
	}


	# @return	string	generate nonce hash (with expiry/timestamp it
	# 					is not good nor really doing what it should);
	#					therefore, log nonce + time for checking purpose

	private function __getNonce(){
		return	hash_hmac('ripemd160', base64_encode(
				  date('Y-m-d H:i:s', ceil(time()/(Maat::LIFESPAN/10)) * (Maat::LIFESPAN/10))
				. 'I' . Maat::$client
				. 'M' . $_SERVER['REMOTE_ADDR']
				. 'P' . Maat::PRIVATE_KEY
			), Maat::PRIVATE_KEY
		);
	}


	# @return	string	generate an (rather) unique ID

	private function __getOpaque(){
		return	hash_hmac('ripemd160', base64_encode(
				  Maat::$domainID . Maat::$client
				. str_replace('.', '', $_SERVER['REMOTE_ADDR'])
			), Maat::PRIVATE_KEY
		);
	}


	# @param	string
	# @param	string

	private function __logNonce($path, $content){
		Elf::writeToFile($path, $content, 'wb');#, 1);
	}

}
