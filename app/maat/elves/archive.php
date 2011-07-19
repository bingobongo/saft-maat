<?php

namespace Saft;


Class Archive {


	# Archive
	#
	# - official zip file format: http://www.pkware.com/appnote.txt
	# - partially based on, kudos to
	#   - The Horde Project, http://horde.org
	#     @package Horde_Compress (framework/compress/lib/Horde/Crompress.php)
	#   - Eric Mueller, <eric@themepark.com>,
	#        http://www.zend.com/codex.php?id=535&single=1
	#   - Deins125, <webmaster@atlant.ru>,
	#        http://www.zend.com/codex.php?id=470&single=1
	#   - patch from Peter Listiak <mlady@users.sourceforge.net>
	#        for last modified date and time of shrinked file
	#
	# - example usage:
	#   --------------
	#   require_once($path_to_dir_where_this_class_resides_in.'/archive.php');
	#   $zip = new Archive([, $dir_path_from_where_we_grab_the_data]);
	#   $zip->add($dir_or_file_path_that_is_child_of_dir_from_where_we_grab);
	#   $zip->saveAs($path_to_where_temporary_zip_archive_is_saved);
	#   $zip->download($path_of_temporary_archive_or_else_file,
	#        $desired_zip_archive_name_on_client_machine
	#        [, 1 = will delete file on server when finished, 0 = not]);

	const
		# zip header (start string of file content)
		DATA_TOP_STR = "\x50\x4b\x03\x04",
		# zip header (start string of central directory record)
		DIR_TOP_STR = "\x50\x4b\x01\x02",
		# "\x14\x00" = version required for extraction
		# "\x00\x00" = general purpose bit flag
		# "\x08\x00" = compression method
		DATA_DIR_TOP_STR = "\x14\x00\x00\x00\x08\x00";

	# current pot path, compiled archive data, compiled directory
	#    structure (central directory record) and offset location
	public static
		$potPath,
		$data,
		$dirs = array(),
		$offset = 0;


	# @param	string or integer
	#			(current) directory from where this instance
	#			may grab data; negligible for flat archives

	public function __construct($potPath = 0){
		self::$potPath = $potPath;
		# 1048576 = 1024 * 1024 = 1 megabyte
		self::$data = fopen('php://temp/maxmemory:1048576', 'r+');
	}


	# @param	string
	# @param	integer	1 = addition to the archive root;
	#					0 = not (allows deep archive structure,
	#					    but each directory must be added separately)

	public static function add($itemPath, $flat = 0){
		$hextime = dechex(self::__unixToDosTime(filemtime($itemPath)));
		$hextime = chr(hexdec($hextime[6] . $hextime[7]))
				 . chr(hexdec($hextime[4] . $hextime[5]))
				 . chr(hexdec($hextime[2] . $hextime[3]))
				 . chr(hexdec($hextime[0] . $hextime[1]));

		$name = (self::$potPath === 0 or $flat === 1)
			? basename($itemPath)
			: substr($itemPath, strlen(self::$potPath) + 1);

		# determine filetype (16 = directory, 32 = file),
		#    names; whereat directory names must end with a slash
		if (is_dir($itemPath) === true){
			$type = 16;

			if (substr($itemPath, -1) !== '/')
				$name.= '/';
		} else
			$type = 32;

		$stream = fopen($itemPath, 'rb');
		$data = stream_get_contents($stream);

		# local file header segments
		$uncLen = strlen($data);
		$crc = crc32($data);
		# gzip data, substr to fix crc bug
		$gzData = substr(gzcompress($data), 2, -4);

		# common data for the both entries
		$common = self::DATA_DIR_TOP_STR
				. $hextime					# last modification time and date
				. pack('V', $crc)			# crc32 information (compression)
				. pack('V', strlen($gzData))# compressed filesize
				. pack('V', $uncLen)		# uncompressed filesize
				. pack('v', strlen($name))	# length of filename
				. pack('v', 0);				# extra field length

		# add to archive data
		fseek(self::$data, 0, SEEK_END);
		$oldOffset = ftell(self::$data);

		fwrite(self::$data,
			   self::DATA_TOP_STR			# begin creating zip data
			 . $common						# common data
			 . $name						# filename
			 . $gzData						# compressed data
		);									#     ("file data" segment)

		# add to central directory record
		self::$dirs[] = self::DIR_TOP_STR
					  . "\x00\x00"			# version made by
					  . $common				# common data
			# end "local file header" ------- begin "data descriptor" ---<
					  . pack('v', 0)		# file comment length
					  . pack('v', 0)		# disk number start
					  . pack('v', 0)		# internal file attribute
					  . pack('V', $type)	# external file attribute type
											#    ("archive" bit set)
					  . pack('V',$oldOffset)# relative offset of local  header
					  . $name;				# filename
	}


	# @param	string
	#
	# write compressed data to file on server, close "php://temp"

	public static function saveAs($tmpnamePath){
		$dir = implode('', self::$dirs);
		$dirLen = sizeof(self::$dirs);
		
		fseek(self::$data, 0, SEEK_END);
		$offset = ftell(self::$data);

		fwrite(self::$data,					# $dir = central directory data
			   $dir							# end string (eof) central directory
			 . "\x50\x4b\x05\x06\x00\x00\x00\x00"
			 . pack('v', $dirLen)			# total number of entries "on disk"
			 . pack('v', $dirLen)			# and the same in file (overall)
			 . pack('V', strlen($dir))		# size of central directroy
			 . pack('V', strlen($offset))	# offset to start of central dir
			 . "\x00\x00"					# zip file comment length
		);

		fseek(self::$data, 0, SEEK_END);
		$offset = ftell(self::$data);
		rewind(self::$data);
		# in respect of speed it is quite the same as fopen,
		#    fwrite, fclose with "$len = ftell()" inside a loop
		$len = 0;
		while ($offset !== $len)
			$len += file_put_contents($tmpnamePath, stream_get_contents(self::$data, 1024 * 8), FILE_APPEND);

		fclose(self::$data);
		clearstatcache();
	}


	# @param	integer
	# @return	array

	private function __unixToDosTime($unixTime = 0){
		$timeArray = $unixTime === 0
			? getdate()
			: getdate($unixTime);

		if ($timeArray['year'] < 1980){
			$timeArray['year'] = 1980;
			$timeArray['mon'] = 1;
			$timeArray['mday'] = 1;
			$timeArray['hours'] = 0;
			$timeArray['minutes'] = 0;
			$timeArray['seconds'] = 0;
		}

		return
			 (($timeArray['year'] - 1980) << 25)
			| ($timeArray['mon'] << 21)
			| ($timeArray['mday'] << 16)
			| ($timeArray['hours'] << 11)
			| ($timeArray['minutes'] << 5)
			| ($timeArray['seconds'] >> 1);
	}


	# @param	string
	# @param	string
	# @param	integer	1 = will delete file on server when finished;
	#					0 = not

	public static function download($tmpnamePath, $zipname, $unlink = 0){
		if (ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);//public');
		header('Content-Description: File Transfer');
		header('Content-Type: ' . ($unlink === 1
			? 'application/octet-stream'
			: 'application/zip')
		);
		header('Content-Disposition: attachment; filename="' . $zipname . '";');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . filesize($tmpnamePath));
		header('Connection: close');
		$zip = fopen($tmpnamePath, 'rb');

		if ($zip){

			while (!feof($zip)){
				print(fread($zip, 1024 * 8));
				flush();

				if (connection_status() !== 0){
					fclose($zip);

					if (	$unlink === 1
						&&	file_exists($tmpnamePath)
					)
						unlink($tmpnamePath);

					exit;
				}
			}

			fclose($zip);

			if (	$unlink === 1
				&&	file_exists($tmpnamePath)
			)
				unlink($tmpnamePath);
		}

		clearstatcache();
		exit;
	}

}
