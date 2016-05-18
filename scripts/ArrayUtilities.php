<?php
/**
 * Class for array manipulation
 *
 * @author: Evgeny A. Stepanov
 * @email : stepanov.evgeny.a@gmail.com
 */
class ArrayUtilities {

	/* Sort & Uniq on 1D arrays */

	/**
	 * Generate all possible combinations of sort & uniq
	 * @param  array   $arr
	 * @param  boolean $flag
	 * @return array   $out
	 */
	public function arrayGenerateOrders($arr, $flag = SORT_NATURAL) {
		$out = array();
		$out['O']  = $arr;                              // original order
		$out['U']  = $this->arrayUniq($arr);            // uniq
		$out['S']  = $this->arraySort($arr, $flag);     // sort
		$out['US'] = $this->arrayUniqSort($arr, $flag); // uniq & sort
		$out['SU'] = $this->arraySortUniq($arr, $flag); // sort & uniq (set)
		return $out;
	}

	/**
	 * Sort array [just an alias]
	 * @param  array  $arr
	 * @param  string $flag
	 * @return array
	 */
	public function arraySort($arr, $flag = SORT_NATURAL) {
		sort($arr, $flag);
		return $arr;
	}

	/**
	 * Join consecutive identical elements [string-wise]
	 * @param  array $arr
	 * @return array $out
	 */
	public function arrayUniq($arr) {
		$out = array();
		foreach ($arr as $e) {
			$prev = (empty($out)) ? NULL : $out[count($out) - 1];
			if ($e !== $prev) {
				$out[] = $e;
			}
		}
		return $out;
	}

	/**
	 * Sort and Uniq array [just an alias]
	 * @param  array  $arr
	 * @param  string $flag
	 * @return array
	 */
	public function arraySortUniq($arr, $flag = SORT_NATURAL) {
		sort($arr, $flag);
		return $this->arrayUniq($arr);
	}

	/**
	 * Uniq and Sort array
	 * @param  array  $arr
	 * @param  string $flag
	 * @return array  $out
	 */
	public function arrayUniqSort($arr, $flag = SORT_NATURAL) {
		$out = $this->arrayUniq($arr);
		sort($out, $flag);
		return $out;
	}

	/* Removing Elements */

	/**
	 * Remove element(s) from an array by value(s)
	 *
	 * @param  array $arr
	 * @param  mixed $e
	 * @return array
	 */
	public function rmElement($arr, $e) {
		if (!is_array($e)) {
			$e = array($e);
		}

		return array_diff($arr, $e);
	}

	/**
	 * Remove element(s) from an array by key(s)
	 *
	 * @param  array $arr
	 * @param  mixed $e
	 * @return array
	 */
	public function rmElementByKey($arr, $e) {
		if (!is_array($e)) {
			$e = array($e);
		}

		return array_diff_key($arr, array_flip($e));
	}

	/* Dimension Reduction */

	/**
	 * Flatten array recursively to single level
	 *    (element arrays might be non-uniform)
	 *
	 * @param  array $arr
	 * @return array $out
	 */
	public function arrayFlattenRecursive($arr) {
		$out = array();
		foreach ($arr as $e) {
			if (is_array($e)) {
				$out = array_merge($out, $this->arrayFlattenRecursive($e));
			}
			else {
				$out[] = $e;
			}
		}
		return $out;
	}

	/**
	 * Flatten array by N dimensions
	 *
	 * @param  array $arr
	 * @param  int   $dim
	 * @return array
	 */
	public function arrayFlatten($arr, $dim = NULL) {
		if ($this->is_flat($arr)) {
			return $arr;
		}
		elseif ($dim === 1 || $dim === NULL) {
			$out = array();
			foreach ($arr as $a) {
				foreach ($a as $e) {
					$out[] = $e;
				}
			}
			return $out;
		}
		else {
			$out = $arr;
			while ($dim > 0) {
				$out = $this->arrayFlatten($out, 1);
				$dim--;
			}
			return $out;
		}
	}

	/**
	 * Flatten array to dimensionality N
	 *
	 * @param  array $arr
	 * @param  int   $dim
	 * @return array
	 */
	public function arrayFlattenToDimension($arr, $dim) {
		$arr_dim = $this->getDimension($arr);

		if ($arr_dim <= $dim) {
			return $arr;
		}
		elseif ($this->is_flat($arr)) {
			return $arr;
		}
		elseif ($dim === 0) {
			return $this->arrayFlattenRecursive($arr);
		}
		else {
			$red_dim = $arr_dim - $dim;
			return $this->arrayFlatten($arr, $red_dim);
		}
	}

	/* Dimensionality Increasing */

	/**
	 * Increase dimensionality of array by N
	 *
	 * @param  array $arr
	 * @param  int   $dim
	 * @return array
	 */
	public function arrayExpand($arr, $dim = NULL) {

		if ($dim === 0) {
			return $arr;
		}
		elseif ($dim === NULL || $dim === 1) {
			$out = array();
			foreach ($arr as $e) {
				$out[] = array($e);
			}
			return $out;
		}
		else {
			$out = $arr;
			while ($dim > 0) {
				$out = $this->arrayExpand($out, 1);
				$dim--;
			}
			return $out;
		}
	}

	/**
	 * Increase dimensionality of array to N
	 *
	 * @param  array $arr
	 * @param  int   $dim
	 * @return array
	 */
	public function arrayExpandToDimension($arr, $dim) {
		$arr_dim = $this->getDimension($arr);

		if ($arr_dim >= $dim) {
			return $arr;
		}
		else {
			$exp_dim = $dim - $arr_dim;
			return $this->arrayExpand($arr, $dim);
		}
	}

	/* Dimension Checking */

	/**
	 * Check whether array is flat == 1D
	 *
	 * @param  array $arr
	 * @return bool
	 */
	public function is_flat($arr) {
		$arr_arr = array_filter($arr, 'is_array');
		if (count($arr_arr) > 0) {
			return FALSE;
		}
		else {
			return TRUE;
		}
	}

	/**
	 * Get array dimension
	 *
	 * @param  array $arr
	 * @return int
	 */
	public function getDimension($arr) {

		if ($this->is_flat($arr)) {
			return 1;
		}
		else {
			$flat_arr = $this->arrayFlatten($arr);
			return 1 + $this->getDimension($flat_arr);
		}
	}

	/* Array Printing */

	/**
	 * Print array using json_encode
	 *
	 * @param  array $arr
	 */
	public function arrayPrint($arr) {
		$i = array('[[', '],[');
		$o = array("[\n[","]\n[");

		echo str_replace($i, $o, json_encode($arr)) . "\n";
	}

	/* Information Arrays */

	/**
	 * Return keys of the GenerateOrders
	 *
	 * @return array
	 */
	public function getOrders() {
		return array('O', 'U', 'S', 'US', 'SU');
	}

	/* Utility Functions */

	/**
	 * Combine 2 arrays into 1, as keys and values
	 * Keeps all values for duplicate keys
	 *
	 * @param  array $keys
	 * @param  array $values
	 * @return array $out
	 */
	public function array_combine_multi($keys, $values) {
		$out = array();
		foreach ($keys as $i => $key) {
			$out[$key][] = $values[$i];
		}
		return $out;
	}

	/* Path Functions */

	/**
	 * Retrieve array element by array of keys as path
	 *
	 * @param  array $arr
	 * @param  array $path
	 * @return mixed
	 */
	public function getValueByPath($arr, $path) {
		$out = &$arr;
		foreach ($path as $key) {
			$out = &$out[$key];
		}
		return $out;
	}

	/**
	 * Set array element by array of keys as path
	 *
	 * @param  array $arr
	 * @param  array $path
	 * @param  mixed $value
	 */
	public function setValueByPath(&$arr, $path, &$value) {
		$out = &$arr;
		foreach ($path as $key) {
			if (!isset($out[$key])) {
				$out[$key] = array();
			}
			$out = &$out[$key];
		}
		$out = $value;
	}

	/**
	 * Add array element by array of keys as path
	 *
	 * @param  array $arr
	 * @param  array $path
	 * @param  mixed $value
	 */
	public function appendValueByPath(&$arr, $path, &$value) {
		$out = &$arr;
		foreach ($path as $key) {
			if (!isset($out[$key])) {
				$out[$key] = array();
			}
			$out = &$out[$key];
		}
		$out = array_merge($out, $value);
	}

	/* Key Manipulation Funcctions */

	/**
	 * Convert numeric array keys to string
	 *  (useful for merging)
	 *
	 * @param  array  $arr
	 * @param  string $str [optional]
	 * @return array  $out
	 */
	public function arrayKeyToString($arr, $str = 's') {
		$out = array();
		foreach ($arr as $k => $v) {
			$out[$str.$k] = $v;
		}
		return $out;
	}

	/**
	 * Convert string array keys to numeric
	 *  (inverse of arrayKeyToString)
	 *
	 * @param  array  $arr
	 * @param  string $str [optional]
	 * @return array  $out
	 */
	public function arrayKeyToNumber($arr, $str = 's') {
		$out = array();
		foreach ($arr as $k => $v) {
			$regex = preg_quote($str);
			$key = preg_replace('/^'. $regex . '/u', '', $k);
			$out[$k] = $v;
		}
		return $out;
	}

}

// Class Test Cases:
/*
$au = new ArrayUtilities();

$a = array('d', 'a', 'b', 'b', 'c', 'd', 'd');
$b = array(
		array(
			array('d', 'a'),
			array('b', 'b'),
		),
		array(
			array('c', 'd'),
			array('d'),
		),
);

echo 'O : ' . json_encode($a) . "\n";
echo 'U : ' . json_encode($au->arrayUniq($a)) . "\n";
echo 'S : ' . json_encode($au->arraySort($a)) . "\n";
echo 'US: ' . json_encode($au->arrayUniqSort($a)) . "\n";
echo 'SU: ' . json_encode($au->arraySortUniq($a)) . "\n";

echo 'Flattening...' . "\n";

foreach (range(0, 10) as $dim) {
	echo 'arrayFlatten: ' . $dim . ': ';
	echo json_encode($au->arrayFlatten($b, $dim)) . "\n";
}

foreach (range(0, 5) as $dim) {
	echo 'arrayFlattenToDimension:' . $dim . ': ' . json_encode($au->arrayFlattenToDimension($b, $dim)) . "\n";
}

foreach (range(0, 5) as $dim) {
	echo 'arrayExpandToDimension:' . $dim . ': ' . json_encode($au->arrayExpandToDimension($a, $dim)) . "\n";
}

foreach (range(0, 5) as $dim) {
	echo 'arrayExpand:' . $dim . ': ' . json_encode($au->arrayExpand($a, $dim)) . "\n";
}
echo 'arrayFlattenRecursive: ' . json_encode($au->arrayFlattenRecursive($b)) . "\n";
echo 'is_flat: ';
echo ($au->is_flat($b)) ? 'TRUE' : 'FALSE' . "\n";
echo 'Dimension Counting: ' . $au->getDimension($b) . "\n";
*/
