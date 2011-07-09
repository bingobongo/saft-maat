<?php

namespace Saft;


Class Client {

	public static $engine,
				  $httpUserAgent,
				  $name,
				  $platform,
				  $version;


	public function __construct(){
		self::$httpUserAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
	}


	# @param	array	“browsername” => “version” where version is a float
	#						e.g. “array('chrome' => 5.0, 'firefox' => 3.6, 'safari' => 4)”;
	# @return	string or integer

	public static function browser($arr = null){
		self::__getClient();

		if (isset($arr[self::$name]) === false)
			return 0;

		if (	empty(self::$version) === false
			&&	self::$version >= floatval($arr[self::$name])
		)
			return self::$name . strval(self::$version);

		return 0;
	}


	# @param	string	e.g. “array('linux', 'mac')”
	# @return	string or integer

	public static function platform($arr = null){
		self::__getPlatform();

		if (isset($arr[self::$platform]) === false)
			return 0;

		else if (	empty(self::$platform) === false
				&&	in_array(self::$platform, $arr) === true
		)
			return self::$name . strval(self::$version);

		return 0;
	}


	private function __getClient(){
		$ua = self::$httpUserAgent;
		$g = 'gecko';									# gecko engine, i.e. Firefox, Camino (mozilla browser)
		$p = 'presto';									# presto engine, i.e. Opera
		$t = 'trident';									# trident engine, i.e. Internet Explorer
		$w = 'webkit';									# webkit engine, i.e. Safari, Google Chrome, Konquerer
		$c = 'chrome';
		$f = 'firefox';
		$k = 'konqueror';
		$o = 'opera';
		$s = 'safari';
		$ie = 'ie';

		if (strpos($ua, 'apple' . $w .'/') !== false){	# webkit
			self::$engine = $w;

			if (strpos($ua, $k) !== false)					# konqueror
				self::$name = $k;

			else if (strpos($ua, 'iron') !== false)			# srware iron
				self::$name = 'iron';

			else if (strpos($ua, $c . '/') !== false){		# chrome
				self::$name = $c;
				self::$version = self::__getVersion($c . '/');

			} else if (strpos($ua, $s . '/') !== false){	# safari
				self::$name = $s;
				self::$version = self::__getVersion('version/');

			} else {
				self::$name =
				self::$version = null;
			}

		} else if (strpos($ua, $g .'/') !== false){		# gecko
			self::$engine = $g;

			if (strpos($ua, $f . '/') !== false){			# firefox
				self::$name = $f;
				self::$version = self::__getVersion($f . '/');

			} else {
				self::$name =
				self::$version = null;
			}

		} else if (strpos($ua, $p .'/') !== false){		# presto
			self::$engine = $p;

			if (strpos($ua, $o . '/') !== false){			# opera
				self::$name = $o;
				self::$version = self::__getVersion('version/');

			} else {
				self::$name =
				self::$version = null;
			}

		} else if (										# trident
				preg_match('{opera|webtv}i', $ua) !== 1
			&&	preg_match('{msie\s(\d)}', $ua, $arr) === 1
		){
			self::$name = $ie;								# internet explorer
			self::$version = intval(substr($arr[1] . '000', 0, 4));

		} else {
			self::$engine =
			self::$name =
			self::$version = null;
		}

		unset($ua, $g, $p, $t, $w, $c, $f, $k, $o, $s, $ie);
	}


	# @param	string
	# @return	integer

	private function __getVersion($pattern){
		return floatval(preg_replace('{^.*?' . $pattern .'([\d\.]+).*?$}i', '$1', self::$httpUserAgent));
	}


	private function __getPlatform(){
		$ua = self::$httpUserAgent;

		if (		strpos($ua, 'j2me') !== false)
			self::$platform = 'mobile';

		else if (	strpos($ua, 'iphone') !== false
				||	strpos($ua, 'ipod') !== false
		)
			self::$platform = 'pod';

		else if (	strpos($ua, 'mac') !== false
				||	strpos($ua, 'darwin') !== false
		)
			self::$platform = 'mac';

		else if (	strpos($ua, 'webtv') !== false)
			self::$platform = 'webtv';

		else if (	strpos($ua, 'win') !== false)
			self::$platform = 'win';

		else if (	strpos($ua, 'freebsd') !== false)
			self::$platform = 'freebsd';

		else if (	strpos($ua, 'x11') !== false
				||	strpos($ua, 'linux') !== false
		)
			self::$platform = 'linux';

		else
			self::$platform = null;

		unset($ua);
	}

}
