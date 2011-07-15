<?php

namespace Saft;


Class History {

	public static							# do not seek for them twice if
		$assets,							#    history is on, 1 = no assets
		$entryPath,
		$contentPot,
		$historyPotRoot,
		$historyEntryRoot,
		$historyLogFile;


	public function __construct(){

		if (empty($_REQUEST['entry']) === false){
			self::$entryPath = App::$potRoot . substr($_REQUEST['entry'], strlen(App::$absolute) - 1);

			$contentPot = substr(self::$entryPath, strlen(App::$potRoot) + 1);
			self::$contentPot = substr($contentPot, 0, strpos($contentPot, '/'));
											# potname
			$this->__setupHistoriography();

		} else								# new entry
			self::$contentPot = Maat::$authorPot;
	}


	private function __setupHistoriography(){

		# /log/history
		$historyRoot =
		$root = Maat::$logRoot . '/history';

		# /log/history/potname
		$root.= '/' . self::$contentPot;
		self::$historyPotRoot = $root;

		# /log/history/potname/yyyymmdd permalink
		$root.= '/' . Elf::cutFileExt(basename(self::$entryPath));
		self::$historyEntryRoot = $root;

		# /log/history/potname/yyyymmdd permalink/change.log
		self::$historyLogFile = $root . '/change.log';

		if (Maat::HISTORY === 1){
			$permsLog = App::$perms['cache'];
			Elf::makeDirOnDemand($historyRoot, $permsLog);
			Elf::makeDirOnDemand(self::$historyPotRoot, $permsLog);
			Elf::makeDirOnDemand(self::$historyEntryRoot, $permsLog);
		}

		unset($historyRoot, $permsLog, $root);
	}


	protected function __updateHistoriography(){

		# always force full cache update when updating,
		#    add all indexes for removal; quite strict because of funky cache

		if (App::CACHE === 1){
			$files = glob(preg_quote(App::$cacheRoot) . '/{*.html,*.json,*.xml}', GLOB_BRACE | GLOB_NOSORT);
			$files = array_filter($files, function($file){
				return preg_match('{^(?:.*\.)?' . preg_quote(App::ARR_CACHE_SUFFIX) . '$}i', basename($file)) === 0;
			});

			$file = App::$cacheRoot . '/' . self::$contentPot . '/' . Elf::cutFileExt(basename(self::$entryPath));
			$files = array_merge($files, array(
				$file . '.html',
				$file . '.json',
				App::$potRoot . '/' . self::$contentPot . '/0_REMOVE-TO-FORCE-CACHE-UPDATE.txt'
			));

			foreach ($files as $file){

				if (file_exists($file) === true)
					unlink($file);
			}

			unset($file, $files);
		}

		if (	Maat::HISTORY === 0			# new entry
			or	empty($_REQUEST['entry']) === true
		)									# do not snapshot
			return null;

		$lastAuthor = $this->__getLastAuthor();

		if (substr($lastAuthor, strrpos($lastAuthor, '-') + 1) === App::$author)
			return null;					# if last author == current one
											#    => log, but NO snapshot
		require_once('archive.php');
		$zip = new Archive();
		$zip->add(self::$entryPath);

		# an alternative to the archive class attempt (flat) would be
		#    "exec(escapeshellcmd('zip -qj ' . $zipNamePath
		#        . ' ' . $pathesCommaSeparated));"

		$this->__getAssets();

		if (self::$assets !== 1){

			foreach (array_keys(self::$assets) as $path)
				$zip->add($path);
		}

		$zip->saveAs(self::$historyEntryRoot . '/' . $lastAuthor . '.zip');

		unset($lastAuthor, $zip);
	}


	# @param	string

	protected function __updateHistoriographyPath($newEntryPath){

		if (is_writable(self::$historyEntryRoot))
			rename(	self::$historyEntryRoot,
					self::$historyPotRoot . '/' . Elf::cutFileExt(basename($newEntryPath))
			);
	}


	// @return	string

	private function __getLastAuthor(){
		$file = fopen(self::$historyLogFile, file_exists(self::$historyLogFile) === true
			? 'r+b'
			: 'a+b'
		);
		$lastAuthor = fgets($file);
		$lastAuthor = strpos($lastAuthor, ' ') === false
			? $_SERVER['REQUEST_TIME'] . '-original'
			: $_SERVER['REQUEST_TIME'] . '-' . preg_replace('{[\d\w-]+\s([\w-]+)\s.+\n?}i', '$1', $lastAuthor);

		rewind($file);
		fwrite($file,  $lastAuthor . ' ' . App::$author . ' ' . $_REQUEST['from']
			. "\n" . file_get_contents(self::$historyLogFile));
		fclose($file);

		return $lastAuthor;
	}


	protected function __getAssets(){

		if (empty(self::$assets) === true){
			self::$assets = Pilot::getEntries(App::$potRoot . '/' . self::$contentPot, $regexB = '{
				^'
				. str_replace(' ', '\s', preg_quote(Elf::cutFileExt(basename(self::$entryPath))))
				. '
				\s\d+
				\.(?:' . App::FILE_EXT . ')
				$
				}ix'
			);

			# prevent from seeking multiple times, 1 = no assets

			if (empty(self::$assets) === true)
				self::$assets = 1;
		}
	}

}
