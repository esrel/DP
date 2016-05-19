<?php
/**
 * Class for removing stop words
 *
 * Requires stop-word list
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
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
			$a = preg_split('/\s/u', $arr, -1, PREG_SPLIT_NO_EMPTY);
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
