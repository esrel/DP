<?php
/**
 * Class to read a file into various formats
 * 
 * 'doc'   : 2D array of lines and tokens (document)
 * 'token' : 1D array of tokens
 * 'line'  : 1D array of lines
 * 'text'  : raw text string
 * 
 * White space is the default token separator
 */
class FileReader {
	
	/**
	 * Reads file into string or array w.r.t. format
	 * @param  file   $file
	 * @param  string $format
	 * @param  bool   $skip   skips empty lines if true [default]
	 * @return mixed
	 */
	public function readFile($file, $format, $skip = true) {
		switch ($format) {
			case 'text':
				return $this->file2text($file);
			break;
			
			case 'line':
				return $this->file2line($file, $skip);
			break;
			
			case 'token':
				return $this->file2tok($file, $skip);
			break;
			
			case 'doc':
				return $this->file2doc($file, $skip);
			break;
			
			default:
				return $this->file2line($file, $skip);
			break;
		}
	}
	
	/**
	 * Splits file into 2D array of lines and tokens
	 * @param  file  $file
	 * @param  bool  $skip // if true skips empty lines
	 * @return array $arr
	 */
	private function file2doc($file, $skip) {
		$arr = array();
		$lines = $this->file2line($file, $skip);
		foreach ($lines as $kl => $line) {
			if ($line == '' && $skip) {
				// skip the line
			}
			elseif ($line == '' && !$skip) {
				$arr[] = array();
			}
			else {
				$arr[] = $this->str2arr($line);
			}
		}
		return $arr;
	}
	
	/**
	 * Splits file into array of tokens
	 * @param  file  $file
	 * @return array $arr
	 */
	private function file2tok($file, $skip) {
		$arr = array();
		$lines = $this->file2line($file, $skip);
		foreach ($lines as $line) {
			$arr = array_merge($arr, $this->str2arr($line));	
		}
		return $arr;
	}
	
	/**
	 * Split string into array of tokens w.r.t. white space
	 * @param  string  $str
	 * @return array   $arr
	 */
	private function str2arr($str) {
		$arr = preg_split('/\s+/u', $str, -1, PREG_SPLIT_NO_EMPTY);
		return $arr;
	}
	
	/**
	 * Read file into array per line
	 * @param  file   $file
	 * @param  bool   $skip
	 * @return array  $arr
	 */
	private function file2line($file, $skip) {
		$arr = array_map('trim', file($file));
		
		if ($skip) {
			$arr = array_values(array_filter($arr));
		}
		
		return $arr;
	}
	
	/**
	 * Read file as raw text string
	 * @param  file   $file
	 * @return string $text
	 */
	private function file2text($file) {
		$text = file_get_contents($file);
		return $text;
	}
	
	
}