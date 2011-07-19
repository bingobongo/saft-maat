<?php

namespace Saft;


Class Colonel extends History {

	public static $dump;


	public function __construct(){

		if (	isset($_REQUEST['from']) === false
			or	isset($_REQUEST['entry']) === false
		)
			$this->__abort();

		if (	preg_match('{^[\w/-]+\s[\w-]+\.(?:' . App::FILE_EXT . ')$}i', $_REQUEST['entry']) === 1
				# new entry
			xor	(	empty($_REQUEST['entry']) === true
				&&	$_REQUEST['from'] === 'publish-entry'
				)
		){
			parent::__construct();
			$from = $_REQUEST['from'];
			# non-existent entry nor new one
			if (	empty($_REQUEST['entry']) === false
				&&	is_writable(self::$entryPath) === false
			)
				$this->__abort();

			switch ($from){
				case 'publish-entry':
					$this->__publishEntry();
					break;
				case 'remove-asset':
					$this->__removeAsset();
					break;
				case 'remove-entry':
					$this->__removeEntry();
					break;
				case 'add-assets':
					$this->__addAssets();
					break;
			}

			clearstatcache();

		} else
			$this->__abort();

		if (empty(self::$dump) === true)
			$this->__abort();

		# output asynchronous dump
		Elf::sendExitHeader(200, 'text/plain');
		echo self::$dump;
	}


	private function __publishEntry(){
		$fileExt = $this->__checkEntryContent(($new = empty(self::$entryPath) === true ? 1 : 0));
		$date =	(	empty($_REQUEST['date']) === true
				or	preg_match('{^\d{8}$}i', $_REQUEST['date']) !== 1)
			? strval(Elf::getCurrentDate())
			: $_REQUEST['date'];

		$permalink = empty($_REQUEST['permalink']) === true
			? hash_hmac('ripemd160', uniqid(
					Maat::$client . str_replace('.', '', $_SERVER['REMOTE_ADDR']), true
				), Maat::PRIVATE_KEY)
			: preg_replace('{[^\w-]}i', '', str_replace(
				array('ä','ö','ü','ß'),
				array('a','o','u','ss'),
				strtolower($_REQUEST['permalink'])
			));

		$newEntryPath = $new === 1
			? App::$potRoot . '/' . self::$contentPot . '/'
			: substr(self::$entryPath, 0, strrpos(self::$entryPath, '/') + 1);
		$newEntryPath.= $date . ' ' . $permalink . $fileExt;

		if ($new === 0){
			$this->__adaptEntry($newEntryPath, $fileExt);
			self::$dump = Elf::entryPathToURLi(self::$entryPath);
			return;
		}

		$this->__createEntry($newEntryPath, $fileExt);

		# from __addAssets
		$dataAssetStr = self::$dump;

		self::$dump = '{
	"article": "article[data-ext='
			. substr($fileExt, 1) . '][data-asset-uri='
			. App::$absolute . substr(self::$entryPath, strlen(App::$potRoot . '/'), - strlen(basename(self::$entryPath)))
			. ']';

		if (empty($dataAssetStr) === false){
			self::$dump.= '[data-asset=' . $dataAssetStr . ']';
			$dataAssetStr = '<span class=tally> + ' . strval(substr_count($dataAssetStr, '|') + 1) . '</span>';

		} else
			$dataAssetStr = '<span class=tally> + 0</span>';

		if (Elf::getCurrentDate() < intval(substr(basename(self::$entryPath), 0, 8)))
			self::$dump.= '[class=scheduled]';

		self::$dump.= '",
	"html": "<a href=/' .  Elf::entryPathToURLi(self::$entryPath, true) . '><span class=turn></span>'
			. Elf::avoidWidow(Elf::getEntryTitle(self::$entryPath)). '<span class=remove> ✖</span>' . $dataAssetStr
			. '</a> "
}';
		unset($dataAssetStr, $fileExt, $newEntryPath);
	}


	# @param	integer
	# @return	string	dot file extension, e.g. ".txt"

	private function __checkEntryContent($new){

		if (empty($_FILES['100']) === true){

			if (empty($_REQUEST['content']) === true){

				# new entry or old entry and text filetype; entry content missing
				if (	$new === 1
					xor	Elf::getFiletype(basename(self::$entryPath)) === 'text'
				)
					$this->__abort();

				$fileExt = '.' . Elf::getFileExt(basename(self::$entryPath));

			} else
				$fileExt = '.txt';

		} else {
			# entry content invalid
			if (($fileExt = $this->__isValidFile($_FILES['100'])) === 0)
				$this->__abort();
		}

		if ($new === 0){
			$this->__updateHistoriography();
			# fetch assets ere the path changes in case of history is off
			$this->__getAssets();
		}

		return $fileExt;
	}


	# @param	string
	# @param	string

	private function __createEntry($newEntryPath, $fileExt){
		$this->__incrementPermalink($newEntryPath, $fileExt);

		if (	Elf::getFiletype($newEntryPath) === 'text'
			&&	empty($_REQUEST['content']) === false
		){
			Elf::writeToFile($newEntryPath, $this->__tidyContent($_REQUEST['content']));
			chmod($newEntryPath, App::$perms['asset_parts']);

		} else if (move_uploaded_file($_FILES['100']['tmp_name'], $newEntryPath)){
			chmod($newEntryPath, App::$perms['asset_parts']);

			unset($_FILES['100']);

		} else
			$this->__abort();

		self::$entryPath = $newEntryPath;
		$this->__addAssets();
		unset($fileExt, $newEntryPath);
	}


	# @param	string
	# @param	string

	private function __adaptEntry($newEntryPath, $fileExt){

		# remove assets (1 = no assets, function __getAssets)
		if (self::$assets !== 1){
			$assets = array_flip(self::$assets);
			$newAssets = isset($_REQUEST['data-asset']) === true
				? explode('|', $_REQUEST['data-asset'])
				: array();
			$assets = array_filter($assets, function($assetPath) use(&$newAssets){
				$IDstr = substr($assetPath, strrpos($assetPath, ' ') + 1);
				return ((isset($_FILES[Elf::cutFileExt($IDstr)]) === true
						or	in_array($IDstr, $newAssets) === false)
					&&	is_writable($assetPath)
					&&	unlink($assetPath))
					? 0
					: 1;
			});

			unset($assetPath, $IDstr, $newAssets);
			# prevent from seeking multiple times, 1 = no assets
			self::$assets = empty($assets) === true
				? 1
				: array_flip($assets);
		}

		# same entry filename and extension
		if (self::$entryPath === $newEntryPath){

			if (	Elf::getFiletype($newEntryPath) === 'text'
				&&	empty($_REQUEST['content']) === false
			)
				Elf::writeToFile($newEntryPath, $this->__tidyContent($_REQUEST['content']), 'w+b');

			else if (empty($_FILES['100']) === false){

				if (move_uploaded_file($_FILES['100']['tmp_name'], $newEntryPath)){
					chmod($newEntryPath, App::$perms['asset_parts']);
					unset($_FILES['100']);
				}
			}

		# different entry filename and/or extension
		} else {
			$this->__incrementPermalink($newEntryPath, $fileExt);

			if (	Elf::getFiletype($newEntryPath) === 'text'
				&&	empty($_REQUEST['content']) === false
			){

				if (	Elf::getFiletype(self::$entryPath) !== 'text'
					or	rename(self::$entryPath, $newEntryPath) === false
				)
					unlink(self::$entryPath);

				Elf::writeToFile($newEntryPath, $this->__tidyContent($_REQUEST['content']), 'w+b');

			} else {

				if (	isset($_FILES['100']) === true
					&&	move_uploaded_file($_FILES['100']['tmp_name'], $newEntryPath)
				){
					unlink(self::$entryPath);
					unset($_FILES['100']);

				# keep old entry file alive on failure
				} else
					rename(self::$entryPath, $newEntryPath);
			}

			chmod($newEntryPath, App::$perms['asset_parts']);

			# different entry filename => rename assets, rebuild array
			if (Elf::cutFileExt(self::$entryPath) !== Elf::cutFileExt($newEntryPath)){

				if (empty($assets) === false){
					$filenamePart = basename($newEntryPath, $fileExt);
					self::$assets = array();

					foreach ($assets as $assetPath){
						$newAssetPath = substr($assetPath, 0, strrpos($assetPath, '/') + 1)
							. $filenamePart . substr($assetPath, strrpos($assetPath, ' '));
						rename($assetPath, $newAssetPath);
						self::$assets[basename($newAssetPath)] = $newAssetPath;
					}
				}

				unset($assetPath, $newAssetPath);
				$this->__updateHistoriographyPath($newEntryPath);
			}

			self::$entryPath = $newEntryPath;
		}

		unset($assets, $fileExt, $filenamePart, $newEntryPath);
		$this->__addAssets();
	}


	# @param	string
	# @param	string
	# @return	string	by reference

	private function __incrementPermalink(&$newEntryPath, $fileExt){
		$filenamePart = basename($newEntryPath, $fileExt);
		$pathPart = Elf::cutFileExt($newEntryPath);
		$entries = Pilot::getEntries(App::$potRoot . '/' . self::$contentPot, $regexB = '{
			^'
			. str_replace(' ', '\s', preg_quote($filenamePart))
			. '
			\.(?:' . App::FILE_EXT . ')
			$
			}ix'
		);

		$i = 1;
		while ($this->__isEntry($entries, basename($newEntryPath, $fileExt)) === 1){
			++$i;
			$newEntryPath = $pathPart . '-' . strval($i) . $fileExt;
		}

		unset($entries, $fileExt, $filenamePart, $i, $pathPart);
	}


	# @param	array
	# @param	string
	# @return	integer

	private function __isEntry(&$entries, $filenamePart){
		$entry = preg_grep('{^' . preg_quote($filenamePart) . '\.(?:' . App::FILE_EXT . ')$}i', $entries);
		return empty($entry) === true
			? 0
			: 1;
	}


	# @param	string
	# @return	string

	private function __tidyContent(&$str){
		return str_replace("\r\n", "\n", stripslashes($str));
	}


	private function __removeAsset(){

		# no entry asset or entry and asset do not match
		if (	empty($_REQUEST['asset']) === true
			or	preg_replace('{.*(\d{4}\d{2}\d{2}\s[\w-]+).*}i', '$1', self::$entryPath) !== preg_replace('{.*(\d{4}\d{2}\d{2}\s[\w-]+).*}i', '$1', $_REQUEST['asset'])
		)
			$this->__abort();

		$assetPath = App::$potRoot . substr($_REQUEST['asset'], strlen(App::$absolute) - 1);

		if (is_writable($assetPath)){
			$this->__updateHistoriography();

			if (unlink($assetPath))
				self::$dump = true;
		}
	}


	private function __removeEntry(){
		$this->__getAssets();

		if (self::$assets !== 1){
			# bit higher memory peak than sizeof-while-round-next-key
			#    (grows with array size, negligible here)
			foreach (array_keys(self::$assets) as $path){

				if (is_writable($path) === true)
					continue;
				else
					return;
			}
		}

		if (is_writable(self::$entryPath) !== true)
			return;

		$this->__updateHistoriography();

		if (self::$assets !== 1){

			foreach (array_keys(self::$assets) as $path){

				if (!unlink($path))
					return;
			}
		}

		if (unlink(self::$entryPath))
			self::$dump = true;
	}


	# @param	string	initialize variable

	private function __addAssets($IDstr = ''){

		if ($_REQUEST['from'] === 'add-assets'){
			# no entry assets
			if (empty($_FILES) === true)
				return;

			$this->__updateHistoriography();
		}

		$pathPart = Elf::cutFileExt(self::$entryPath) . ' ';
		$takenIDstr = $this->__getTakenAssetIDs();

		# bit higher memory peak than sizeof-while-round-next-key
		#    (grows with array size, negligible here)
		foreach (array_keys($_FILES) as $ID){
			$file = $_FILES[$ID];

			if (($fileExt = $this->__isValidFile($file)) === 0 or $ID === '100')
				continue;

			$assetPath = $pathPart . $ID . $fileExt;
			# seek for lowest free ID
			if (	strpos($takenIDstr, '|' . $ID . '|') !== false
				or	file_exists($assetPath) === true
			){
				$ID = 1;
				while (strpos($takenIDstr, '|' . $ID . '|') !== false)
					++$ID;

				$assetPath = $pathPart . $ID . $fileExt;
			}

			if (move_uploaded_file($file['tmp_name'], $assetPath)){
				chmod($assetPath, App::$perms['asset_parts']);
				# keep taken ID string up to date
				$takenIDstr.= $ID . '|';
				$IDstr.= $IDstr === ''
					? $ID . $fileExt
					: '|' . $ID . $fileExt;
			}
		}

		self::$dump = $IDstr;
		unset($assetPath, $assetPathPart, $file, $fileExt, $ID, $IDstr, $takenIDstr);
	}


	# @return	string

	private function __getTakenAssetIDs(){
		$this->__getAssets();

		if (self::$assets !== 1){
			$takenIDstr = '|';
			foreach(self::$assets as $name){
				$n = strrpos($name, ' ') + 1;
				$takenIDstr.= substr($name, $n, strrpos($name, '.') - $n) . '|';
			}

			unset($name, $n);
			return $takenIDstr;
		}

		return '|';
	}


	# @param	array
	# @return	string or integer

	private function __isValidFile(&$file){
		return	(	$file['error'] === 0
				&&	($fileExt = $this->__getValidFileExt($file['name'])) !== 0
				&&	is_file($file['tmp_name']) === true
				&&	is_uploaded_file($file['tmp_name']) === true
				&&	$this->__isValidMimeType($file['tmp_name'], $file['type']) === 1
				&&	$file['size'] <= (intval(Maat::MAX_FILE_SIZE) * 1024 * 1024)
			)
			? '.' . $fileExt
			: 0;
	}


	# @param	string
	# @return	string or integer

	private function __getValidFileExt($str){
		return preg_match('{^.+\.(?:' . App::FILE_EXT . ')$}i', $str) === 0
			? 0
			: Elf::getFileExt($str);
	}


	# @param	string
	# @param	string
	# @return	integer	will check MIME type twice; the transferred one
	#					and the one that the FILEINFO extension will return

	private function __isValidMimeType($path, $mimeType){

		if (preg_match('{^(?:image/(?:gif|jpeg|png|webp)|text/plain|video/webm)$}i', $mimeType) === 0)
			return 0;

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $path);
		finfo_close($finfo);
		# "|application/octet-stream" for hosts that do not know video/webm
		return preg_match('{^(?:image/(?:gif|jpeg|png|webp)|text/plain|video/webm|application/octet-stream)$}i', $mimeType) === 0
			? 0
			: 1;
	}


	private function __abort(){
		Elf::sendExitHeader(400, 'text/xml');
		exit;
	}

}
