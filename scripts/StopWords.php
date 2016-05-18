<?php
/**
 * Class for removing stop words
* @author estepanov
*
* Requires stop-word list
*/
class StopWords {

	private $lexicon = array();

	/**
	 * Initiates class by constructing lexicon
	 * @param file $file	lexicon file
	 */
	function __construct($file) {
		$this->lexicon = array_map('trim', file($file));
	}

	/**
	 * remove stop words from string or array
	 * @param unknown $arr
	 */
	public function removeStopWords($arr) {
		if (is_array($arr)) {
			return array_diff($arr, $this->lexicon);
		}
		elseif (is_string($arr)) {
			$a = preg_split('/\s+/u', $arr, -1, PREG_SPLIT_NO_EMPTY);
			$d = array_diff($a, $this->lexicon);
			return implode(' ', $d);
		}
		
	}
	
	/**
	 * Checks if a string is a stop word
	 * @param string $str
	 * @return boolean
	 */
	public function isStopWord($str) {
		$str = strtolower($str);
		
		if (in_array($str, $this->lexicon)) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
}