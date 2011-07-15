<?php

namespace Saft;


Class Copilot extends Pilot {


	public function __construct(){
		$this->__check();
		parent::__construct();
	}


	private function __check(){
		$this->__lang();

		require_once('client.php');
		$client = new Client();
		Maat::$client = $client->browser(array('chrome' => 9.0, 'firefox' => 4.0));
		unset($client);

		if (Maat::$client === 0)
			$this->__initialize('blues', 'app/maat/');

		if (($spell = $this->__maatDecoded()) === 0)
			Elf::sendExit(500, 'Automatically shut down due to the misconfiguration of the “' . ucfirst(App::$author) . '”.');

		if ($this->__auth($spell) !== 1)
			$this->__initialize('blues', 'app/maat/');

		if (isset($_REQUEST['entry']) === true){
			require_once('history.php');
			require_once('colonel.php');
			$colonel = new Colonel();
			unset($colonel);
			exit;
		}
	}


	private function __lang(){
		$path = App::$propelRoot . '/lang/';

		if (	array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) === false
			or	strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) < 2
			or	is_readable($path . ($lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) . '.json')) === false
		){

			if (is_readable($path . ($lang = App::LANG . '.json')) === false){

				if (is_readable($path . ($lang = 'en.json')) === false)
					Elf::sendExit(500, 'Automatically shut down due to a non-existent translation.');
			}
		}

		Maat::$lang = json_decode(file_get_contents($path . $lang), true);
		unset($lang, $path);
	}


	# @return	string or integer

	private function __maatDecoded(){
		$conf = json_decode(file_get_contents(App::$propelRoot . '/authors/' . App::$author . '.json'), true);

		if (	is_array($conf) === true
			&&	empty($conf['spell']) === false
		){
			Maat::$authorPot = empty($conf['content_pot']) === false
				? trim($conf['content_pot'], ' /')
				: App::$author;

			Elf::makeDirOnDemand(App::$potRoot . '/' . Maat::$authorPot, App::$perms['asset']);
			Elf::makeDirOnDemand(Maat::$root . '/log', App::$perms['cache']);
			Elf::makeDirOnDemand(Maat::$logRoot, App::$perms['cache']);
			$conf = $conf['spell'];

		} else
			$conf = 0;

		return $conf;
	}


	# @param	array, integer or string
	# @param	integer or string
	# @param	integer or string
	# @param	integer

	protected function __setRoute(&$params, &$page, &$rw, &$size){

		switch ($page){
			case null:

				# html root (page 1)

				$this->__initialize('index');
				break;

			default:

				if ($this->__filterRoute($params, $page, $rw, $size) === 0)
					break;

				unset($page, $params, $rw, $size);
				$this->__initialize('index');
				break;
		}

		# 404 not found

		Elf::sendExit(404, 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found on this server.');
	}


	# @param	array
	# @return	string

	public static function toDataAssetStr(&$assets){

		if (empty($assets) === false){
			$str = '';

			# bit higher memory peak than sizeof-while-round-next-key
			#    (grows with array size, negligible here)

			foreach ($assets as $name){
				$n = strrpos($name, ' ') +1;
				$n = substr($name, $n);		# without file extension this would be
											# substr($name,$n,strrpos($name,'.')-$n)
				$str.= $str === ''
					? ' data-asset=' . $n
					: '|' . $n;
			}

			unset($assets, $n, $name);

			return $str;
		}

		return '';
	}

}
