<?php
/***
 * Read/Write class for IOB format files
 *
 * Formats  : suffix|prefix
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class IobReader {

	private $format = 'suffix'; // tag format: suffix|prefix
	private $glue   = '-';      // label & tag glue
	private $sep    = "\t";     // column  separator
	private $eos    = "\n";     // segment separator

	/**
	 * Constructor: set
	 *    tag format
	 *    label & tag glue
	 *    column separator
	 *    segment separator
	 *
	 * @param string $format
	 * @param string $glue
	 * @param string $sep
	 * @param string $eos
	 */
	public function __construct($format = 'suffix',
								$glue = '-',
								$sep = "\t",
								$eos = "\n") {
		$this->format = $format;
		$this->glue   = $glue;
		$this->sep    = $sep;
		$this->eos    = $eos;
	}

	/**
	 * Read TSV file in IOB format
	 *
	 * @param  file   $file
	 * @param  string $format
	 * @param  int    $column  column index to read
	 * @return array  $out
	 */
	public function iobRead($file, $format = 'suffix', $column = NULL) {
		// read file
		$lines_arr  = array_map('trim', file($file));

		$out   = array();
		$segID = 0;
		foreach ($lines_arr as $line) {
			if ($line != '') {
				$col_arr = explode($this->sep, $line);
				$index   = ($column) ? $column : count($col_arr) - 1;
				$out[$segID][] = $this->iobSplit(
										$col_arr[$index],
										$format);
			}
			else {
				$segID++;
			}
		}
		return $out;
	}

	/**
	 * Parse array into IOB array: document-level
	 *
	 * @param  array  $arr
	 * @param  string $format
	 * @retunr array  $out
	 */
	public function iobReadDoc($arr, $format = 'suffix') {
		$out = array();
		foreach ($arr as $segID => $seg) {
			$out[$segID] = $this->iobSplitSeg($seg, $format);
		}
		return $out;
	}

	/**
	 * Parse array into IOB array: segment-level
	 *
	 * @param  array  $arr
	 * @param  string $format
	 * @retunr array  $out
	 */
	public function iobReadSeg($arr, $format = 'suffix') {
		$out = array();
		foreach ($arr as $e) {
			$out[] = $this->iobSplit($e, $format);
		}
		return $out;
	}

	/* STDOUT functions */

	/**
	 * Default behavior for writing functions
	 *
	 * @param  array  $arr
	 * @param  string $format
	 */
	public function iobWrite($arr, $format = 'suffix') {
		$this->iobWriteDoc($arr, $format);
	}

	/**
	 * IOB writing: document-level array
	 *
	 * @param  array  $arr
	 * @param  string $format
	 */
	public function iobWriteDoc($arr, $format = 'suffix') {
		foreach ($arr as $seg) {
			$this->iobWriteSeg($seg);
		}
	}

	/**
	 * IOB writing: segment-level array
	 *
	 * @param  array  $arr
	 * @param  string $format
	 */
	public function iobWriteSeg($arr, $format = 'suffix') {
		foreach ($arr as $segID => $seg) {
			foreach ($seg as $tokID => $tok) {
				echo $this->iobJoin($tok, $format) . "\n";
			}
			echo $this->eos;
		}
	}

	/* Support Functions */

	/**
	 * Remove IOB-tag & return label
	 *
	 * @param string $str
	 * @param string $format
	 * return string
	 */
	public function rmTag($str, $format = 'suffix') {
		$arr = $this->iobSplit($str, $format);
		return $arr[0];
	}

	/**
	 * Parse IOB string into label and tag array
	 *
	 * @param  string $str
	 * @param  string $format
	 * @return array
	 */
	public function iobSplit($str, $format = 'suffix') {
		$arr = explode($this->glue, $str);
		if ($format == 'suffix') {
			return $arr;
		}
		else {
			return array_reverse($arr);
		}
	}

	/**
	 * Join parsed IOB label and tag array into a string
	 *
	 * @param  array  $arr
	 * @param  string $format
	 * @return string
	 */
	public function iobJoin($arr, $format = 'suffix') {
		if ($format == 'suffix') {
			return implode($this->glue, $arr);
		}
		else {
			return implode($this->glue, array_reverse($arr));
		}
	}
}
