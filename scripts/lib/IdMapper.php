<?php
/***
 * Class to re-map 2D array with IDs
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */

require_once 'ArrayUtilities.php';

class IdMapper extends ArrayUtilities {

	// Parameter to remove or not ID columns
	private $rm;

	/**
	 * Constructor: set whether to remove ID columns
	 * @param bool $rm
	 */
	public function __construct($rm = TRUE) {
		$this->rm = $rm;
	}

	/**
	 * Re-maps data array with IDs
	 *
	 * @param  array $data    data array
	 * @param  array $ind_arr array of indices
	 * @param  int   $index   column ID for values
	 * @return array $out
	 */
	public function remap($data, $ind_arr, $index = NULL) {
		$out = array();
		foreach ($data as $row) {
			// create value
			if ($index !== NULL) {
				$value = array($row[$index]);
			}
			elseif ($this->rm) {
				$value = $this->rmElementByKey($row, $ind_arr);
			}
			else {
				$value = $row;
			}

			// get IDs
			$ids = array_intersect_key($row, array_flip($ind_arr));
			$this->appendValueByPath($out, $ids, $value);
		}
		return $out;
	}

	/**
	 * Get subset of data w.r.t. array of IDs
	 *
	 * @param  array $data
	 * @para   array $ids
	 * @return array $out
	 */
	public function getSubsetByID($data, $ids) {
		$out = array();
		foreach ($ids as $id => $e) {
			if (is_array($e)) {
				$out[$id] = $this->getSubsetByID($data[$id], $e);
			}
			else {
				$out[$e] = $data[$e];
			}
		}
		return $out;
	}
}
