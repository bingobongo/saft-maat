<?php

namespace Saft;


Class History {

	public static $assets,								# don’t seek for them twice if history is on (“1” = no assets)
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

		} else											# new entry
			self::$contentPot = Maat::$authorPot;
	}


	private function __setupHistoriography(){
		$historyRoot =
		$root = Maat::$logRoot . '/history';			# /log/history

		$root.= '/' . self::$contentPot;
		self::$historyPotRoot = $root;					# /log/history/potname

		$root.= '/' . Elf::cutFileExt(basename(self::$entryPath));
		self::$historyEntryRoot = $root;				# /log/history/potname/yyyymmdd permalink
		self::$historyLogFile = $root . '/change.log';	# /log/history/potname/yyyymmdd permalink/change.log

		if (Maat::HISTORY === 1){
			$logPerms = App::$perms['cache'];
			Elf::makeDirOnDemand($historyRoot, $logPerms);
			Elf::makeDirOnDemand(self::$historyPotRoot, $logPerms);
			Elf::makeDirOnDemand(self::$historyEntryRoot, $logPerms);
		}

		unset($historyRoot, $logPerms, $root);
	}


	protected function __updateHistoriography(){
														# always force full cache update when updating –
		if (App::CACHE === 1){							#    quite strict because of funky caching
			$files = glob(preg_quote(App::$cacheRoot) . '/{*.html,*.json,*.xml}', GLOB_BRACE | GLOB_NOSORT);
			$files = array_filter($files, function($file){	# add all indexes for removal
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

		if (	Maat::HISTORY === 0
			or	empty($_REQUEST['entry']) === true		# new entry
		)												# don’t snapshot
			return null;

		$lastAuthor = $this->__getLastAuthor();

		if (substr($lastAuthor, strrpos($lastAuthor, '-') + 1) === App::$author)
			return null;								# if the last author == the current one, log, but don’t snapshot

														# an alternative to the archive class attempt (flat) ’d be:
		require_once('archive.php');					#    “exec(escapeshellcmd('zip -qj ' . $zipNamePath
		$zip = new Archive();							#        . ' ' . $pathesCommaSeparated));”
		$zip->add(self::$entryPath);

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

			if (empty(self::$assets) === true)
				self::$assets = 1;						# prevent from seeking multiple times (“1” = no assets)
		}
	}

}
