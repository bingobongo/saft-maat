<?php

namespace Saft;


Class Maat extends App {
											#                 0 = off, 1 = on
	const									# -- START CONFIG -----------------
		HISTORY = 0,						# keep track of authorship, store
											#    entry history in /log/history
		LIFESPAN = 3600,					# authentication lifespan while
											#    requesting nothing in sec.
		MAX_FILE_SIZE = '3M',				# max. file size for uploads,
											#    e.g. 1M = 1 megabyte
		PRIVATE_KEY = '0fecd9d17806ff60f519ac441cbaa14a54802d6c';
											# string to secure nonce encryption
											#    (http digest authentication),
											#    e.g. get the RIPEMD-160 hash
											#    of "x"; open Terminal, type:
											#    "echo -n 'x' | openssl rmd160"
											# -- END CONFIG -------------------

		# DO NOT EDIT ANYTHING BELOW HERE, UNLESS ONE IS DOING SOMETHING CUSTOM

	public static
		$authorPot,
		$client,
		$domainID,
		$lang,
		$logRoot;


	public function __construct($appRoot){

		if (App::DEBUG_MODE === 1){
			require_once('elves/env.php');
			$env = new Env_Maat();
			unset($env);
		}

		if ($this->__extend() !== 0){
			self::$propelRoot = __DIR__;	# point to maat
			self::$assetRoot = $appRoot . '/asset/maat';
			self::$cacheRoot = $appRoot . '/cache/maat';
			self::$logRoot = $appRoot . '/log/maat';
			self::$baseURI = trim(App::$absolute, '/') . '/maat/' . App::$author . '/';
			self::$baseURL = 'http://' . $_SERVER['HTTP_HOST'] . App::$absolute . 'maat/' . App::$author . '/';
			self::$today = 99999999;		# let scheduled entries list
			self::$domainID = Elf::getDomainID();

			require_once('elves/copilot.php');
			require_once('elves/auth.php');
			new Auth();
		}
	}


	# @return	string
	#
	#			update $rw (get rid of "maat/"; make ready for author
	#			name check and get rid of the author name, too;
	#			finally, trim slashes, spaces to make ready for routing)

	private function __extend(){
		$rw = substr(App::$rw, 5) . '/';

		if ((App::$author = $this->__isAuthor(strtolower(Elf::strShiftFirst($rw, '/')))) !== 0)
			App::$rw = $rw === false
				? $rw
				: trim($rw, ' /');

		return App::$author;
	}


	# @param	string
	# @return	string or integer	0 = invalid author name

	private function __isAuthor($name){
		return (empty($name) === true
			or	preg_match('{^[\w-]+$}i', $name) === 0
			or	is_readable(App::$root . '/app/maat/authors/' . $name . '.json') === false)
			? 0
			: $name;
	}

}
