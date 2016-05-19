<?php
/***
 * Dependency (Stanford) to array as
 *
 * Input:
 * array(
 * 	array($function, $headStr-$headID, $tokStr-$tokID),
 * 	...
 * );
 *
 * Output:
 * array(
 * 	$tokID => array($tokStr, $headID, $function),
 *  ...
 * );
 *
 * Note: token IDs start from 1 in input & from 0 in output
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class DependencyReader {

	private $tsep = '-'; // token & ID separator

	/**
	 * Parse dependency array
	 *
	 * @param  array $arr
	 * @return array $tok_arr
	 */
	public function readDependency($arr) {
		// triplets to token-level array
		$tok_arr = array();
		foreach ($arr as $triplet) {
			$type = $triplet[0];

			// head
			$h_arr = explode($this->tsep, $triplet[1]);
			$h_tok = implode($this->tsep, array_slice($h_arr, 0, count($h_arr) - 1));
			$h_pos = $h_arr[count($h_arr) - 1];
			// dependent
			$d_arr = explode($this->tsep, $triplet[2]);
			$d_tok = implode($this->tsep, array_slice($d_arr, 0, count($d_arr) - 1));
			$d_pos = $d_arr[count($d_arr) - 1];

			$tok_arr[$d_pos - 1] = array(
					'token' => $d_tok,
					'head'  => $h_pos - 1,
					'type'  => $type,
			);
		}

		return $tok_arr;
	}
}
