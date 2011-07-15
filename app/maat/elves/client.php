<?php

namespace Saft;


Class Client {

	public static
		$engine,
		$httpUserAgent,
		$name,
		$platform,
		$version;


	public function __construct(){
		self::$httpUserAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
	}


	# @param	array	"browsername" => "version"
	#					 where version is a float,
	#					 e.g. array('chrome' => 5, 'firefox' => 3.6)
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


	# @param	string	e.g. array('linux', 'mac')
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

		if (	preg_match('{opera|webtv}i', $ua) !== 1
			&&	preg_match('{msie\s([\d\.]+)}', $ua, $arr) === 1
		){
			self::$engine = 'trident';
			self::$name = 'ie';
			self::$version = floatval($arr[1]);

		} else if (strpos($ua, 'konquerer/') !== false){
			self::$engine = 'webkit';
			self::$name = 'konquerer';
			self::$version = self::__getVersion('konquerer/');

		} else if (strpos($ua, 'applewebkit/') !== false){
			self::$engine = 'webkit';

			if (strpos($ua, 'iron/') !== false){
				self::$name = 'iron';
				self::$version = self::__getVersion('iron/');

			} else if (strpos($ua, 'flock/') !== false){
				self::$name = 'flock';		# newer => WebKit
				self::$version = self::__getVersion('flock/');

			} else if (strpos($ua, 'fluid/') !== false){
				self::$name = 'fluid';
				self::$version = self::__getVersion('fluid/');

			} else if (strpos($ua, 'omniweb/') !== false){
				self::$name = 'omniweb';
				self::$version = self::__getVersion('omniweb/');

			} else if (strpos($ua, 'shiira/') !== false){
				self::$name = 'shiira';
				self::$version = self::__getVersion('shiira/');

			} else if (	strpos($ua, 'chrome/') !== false
					&&	strpos($ua, 'version/') === false
			){								# wanna Chrome, not Safari
				self::$name = 'chrome';
				self::$version = self::__getVersion('chrome/');

			} else if (strpos($ua, 'safari/') !== false){
				self::$name = $s;
				self::$version = self::__getVersion('version/');
			}

		} else if (strpos($ua, 'gecko/') !== false){
			self::$engine = 'gecko';

			if (strpos($ua, 'flock/') !== false){
				self::$name = 'flock';		# older => Gecko
				self::$version = self::__getVersion('flock/');

			} else if (strpos($ua, 'firefox/') !== false){
				self::$name = 'firefox';
				self::$version = self::__getVersion('firefox/');

			} else if (strpos($ua, 'camino/') !== false){
				self::$name = 'camino';
				self::$version = self::__getVersion('camino/');
			}

		} else if (strpos($ua, 'presto/') !== false){
			self::$engine = 'presto';

			if (strpos($ua, 'opera/') !== false){
				self::$name = 'opera';
				self::$version = self::__getVersion('opera/');
			}
		}

		unset($arr, $ua);
	}


	# @param	string
	# @return	integer

	private function __getVersion($pattern){
		return floatval(preg_replace('{^.*?' . $pattern .'([\d\.]+).*?$}i', '$1', self::$httpUserAgent));
	}


	private function __getPlatform(){
		$ua = self::$httpUserAgent;

		if (	strpos($ua, 'j2me') !== false
			||	strpos($ua, 'mini/') !== false
			||	strpos($ua, 'mobi/') !== false
			||	strpos($ua, 'mobile') !== false
		)
			self::$platform = 'mobile';

		else if (strpos($ua, 'ipad') !== false)
			self::$platform = 'ipad';

		else if (strpos($ua, 'iphone') !== false)
			self::$platform = 'iphone';

		else if (strpos($ua, 'ipod') !== false)
			self::$platform = 'ipod';

		else if (	strpos($ua, 'mac') !== false
				||	strpos($ua, 'darwin') !== false
		)
			self::$platform = 'mac';

		else if (strpos($ua, 'webtv') !== false)
			self::$platform = 'webtv';

		else if (strpos($ua, 'win') !== false)
			self::$platform = 'win';

		else if (strpos($ua, 'freebsd') !== false)
			self::$platform = 'freebsd';

		else if (	strpos($ua, 'x11') !== false
				||	strpos($ua, 'linux') !== false
		)
			self::$platform = 'linux';

		unset($ua);
	}

}
