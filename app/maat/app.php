<?php

namespace Saft;


Class Maat extends App {								#                 0 = off, 1 = on
														# -- START CONFIG ---------------
	const HISTORY = 0,									# keep track of authorship, store entry history in “/log/history”
		  LIFESPAN = 3600,								# authentication lifespan while requesting nothing in sec.
		  MAX_FILE_SIZE = '3M',							# max. file size for uploads, e.g. “1M” = 1 megabyte
		  PRIVATE_KEY = '0fecd9d17806ff60f519ac441cbaa14a54802d6c';
														# string to secure nonce encryption (digest authentication)
														#    e.g. get the RIPEMD-160 hash of “privatekey”;
														#    open Terminal, type: “echo -n 'privatekey' | openssl rmd160”
														# -- END CONFIG -----------------
	public static $authorPot,
				  $client,
				  $domainID,
				  $lang,
				  $logRoot;


	public function __construct($appRoot){
		self::$propelRoot = __DIR__;					# point to maat
		self::$assetRoot.= '/maat';
		self::$cacheRoot.= '/maat';						# incl. scheduled ones
		self::$logRoot = $appRoot . '/log/maat';
		self::$baseURI = trim(App::$absolute, '/') . '/maat/' . App::$author . '/';
		self::$baseURL = 'http://' . $_SERVER['HTTP_HOST'] . App::$absolute . 'maat/' . App::$author . '/';
		self::$today = 99999999;						# far future date to list scheduled entries, too
		self::$domainID = Elf::getDomainID();

		require_once('elves/copilot.php');
		require_once('elves/auth.php');

		new Auth();
	}

}
