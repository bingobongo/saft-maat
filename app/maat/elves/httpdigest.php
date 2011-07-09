<?php

namespace Saft;


Class Httpdigest {


	# Httpdigest
	#
	# http://en.wikipedia.org/wiki/Digest_access_authentication
	# inspired and partially based on Paul James’
	#		“HTTP Authentication with HTML Forms”, http://www.peej.co.uk/articles/http-auth-with-html-forms.html and
	#		“PHP HTTP Digest”, http://www.peej.co.uk/projects/phphttpdigest.html


	# @param	string
	# @param	string
	# @return	string or integer	“0” = failed;
	#								“3” = expired or request count mismatch (reboot);
	#								“4” = kicked out, same user logged in with different clients simultaneously;
	#								“5” = something else’s faulty (error, reboot);
	#								“null” as string = logout; similar to “quit”, but without writing to any log;
	#								“quit” as string = logout;
	#									everything else will be just fine

	public static function authenticate(&$headerAuth, &$spell){

		if (	preg_match('/username="([^"]+)"/', $headerAuth, $name)
			&&	preg_match('/nonce="([^"]+)"/', $headerAuth, $nonce)
			&&	preg_match('/response="([^"]+)"/', $headerAuth, $response)
			&&	preg_match('/opaque="([^"]+)"/', $headerAuth, $opaque)
			&&	preg_match('/uri="([^"]+)"/', $headerAuth, $URI)

		){

			if (preg_match('{^[\w-]+$}i', $name[1]) === 0)
				return 0;								# the author name may not have any special characters

			$name = strtolower($name[1]);
			$nonce = $nonce[1];
			$path = Maat::$logRoot . '/' . App::$author;

														# BEWARE: request count reset’s DIRTY: waive it when possible!
														#    reset it to 0 on logout when the browser is Chrome,
			if (	$name === 'null'					#    because Chrome will reset it after logout; though Firefox
				xor	$name === 'quit'					#    won’t, reset it all the same, because of rare edge cases
			){											#    (client will cable “quit” for logout, “null” for reset only)

				$path.= '/' . 'auth.log';				# start request count reset …
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

				return $name;								# allow to log logout, don’t log reset
			}

			if (	$name === App::$author				# make sure that the name in the header and the URI are equal
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

				if ($response[1] === $expectedResponse){	# start nonce log …
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
						if ($offset === 0){							#    nonce may be on the very first line only

							#    , nonce omitted
							# $n = request count
							# $c = orignal birth time of nonce (= user session start time)
							# $e = expiration time of nonce
							list(, $n, $c, $e) = explode('%', substr($content, 0, strpos($content, "\n")));
																		# make sure that the current request count is
																		#    greater than the logged one, at least +1
																		#    (less strict this’d be 
							if (	1 + intval($n) <= $requestCount		#     “intval($n) < $requestCount”);
								&&	(Maat::LIFESPAN * 8 + $c) > $time	#    session may exceed lifespan eightfold;
								&&	$e > $time							#    after that force to log in once again
							){
								if ($nonceOK === true){						# update request count, extend expiry
									$content = $nonce . '%' . strval($requestCount) . '%' . $c
										. '%' . ($time + Maat::LIFESPAN) . "\n"
										. substr($content, strpos($content, "\n") + 1);
									self::__logNonce($path, $content);
																			# stale: client should re-send with
																			#    new nonce provided (remember me);
								} else										#    log that nonce with request count “0”
									exit(self::sendAuthHeader($path, $c, $content));
																		# authentication lifespan expired or
							} else										#    request count mismatch occured => reboot!
								$name = 3;
																	# not the latest nonce,
																	#    presumably a man-in-the-middle attack; or the
						} else										#    user logged in simultaneously with multiple
							$name = 4;								#    clients, ’ll kick the last one out => reboot!

					} else if ($nonceOK === true){				# user probably logged in just before, log new nonce or
						$content = $nonce . '%' . $requestCount . '%' . $time
							. '%' . ($time + Maat::LIFESPAN) . "\n" . $content;
						self::__logNonce($path, $content);

					} else										#    there’s simply something else’s faulty => reboot!
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

		Elf::sendHttpHeader(401);						# 401 aims to suppress the native http auth box of the client
		header('WWW-Authenticate: Digest realm="' . self::__getRealm()
			. '", domain="' . App::$absolute . App::$author
			. '/", qop=auth, algorithm=MD5, nonce="' . $nonce
			. '", opaque="' . self::__getOpaque() . $stale
		);
	}


	# @return	string	generate realm

	private function __getRealm(){						# kingly name of the app or similar
		return hash('ripemd160', base64_encode('saftmaat@' . Maat::$domainID));
	}


	# @return	string	generate nonce hash with expiry, but expiry with timestamp is wanky and not really
	#					doing what it should do; therefore, log nonce n’ time for checking purpose

	private function __getNonce(){
		return	hash_hmac('ripemd160', base64_encode(
				  date('Y-m-d H:i:s', ceil(time()/(Maat::LIFESPAN/10)) * (Maat::LIFESPAN/10))
				. 'I' . Maat::$client					# $time = ceil(time() / $this->nonceLife) * $this->nonceLife;
				. 'M' . $_SERVER['REMOTE_ADDR']
				. 'P' . Maat::PRIVATE_KEY
			), Maat::PRIVATE_KEY
		);
	}


	# @return	string	generate an (rather) unique ID

	private function __getOpaque(){
		return	hash_hmac('ripemd160', base64_encode(Maat::$domainID
				. Maat::$client
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
