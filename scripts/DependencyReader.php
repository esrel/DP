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
 * @author Evgeny A. Stepanov
 * @e-mail stepanov.evgeny.a@gmail.com
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
