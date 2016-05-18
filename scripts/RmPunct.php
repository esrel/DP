<?php
/**
 * Class to remove punctuation:
 *
 *   Removes all punctuation tokens from string or array
 *   Implements 'trim'-like beahavior:
 *      removing only leading and trailing punctuation
 *
 * @author Evgeny A. Stepanov
 * @e-mail stepanov.evgeny.a@gmail.com
 */
class RmPunct {

	// punctuation to remove
	private $punct;

	/**
	 * Constructor: sets array of punctuation to remove
	 *
	 * @param array $arr
	 */
	public function __construct($arr = NULL) {
		if ($arr !== NULL) {
			if (is_array($arr)) {
				$this->punct = $arr;
			}
			elseif (is_file($arr)) {
				$this->punct = array_map('trim', file($arr));
			}
			else {
				die('Input must be an array or token per line file' . "\n");
			}
		}
		else {
			$this->punct = NULL;
		}
	}

	/**
	 * Removes punctuation from a string or array
	 *  all or trailing and leading only (trim behavior)
	 *
	 * @param  mixed   $input
	 * @param  boolean $trim
	 * @return mixed
	 */
	public function removePunctuation($input, $trim = FALSE) {
		if (is_array($input)) {
			if ($trim) {
				return $this->rmPunctSeqBE($input);
			}
			else {
				return $this->rmPunct($input);
			}
		}
		else {
			$arr = preg_split('/\s/u', $input, -1, PREG_SPLIT_NO_EMPTY);
			if ($trim) {
				$out = $this->rmPunctSeqBE($arr);
			}
			else {
				$out = $this->rmPunct($arr);
			}
			return implode(' ', $out);
		}
	}

	/**
	 * Remove leading and trailing punctuation
	 *
	 * @param  array $arr
	 * @return array
	 */
	private function rmPunctSeqBE($arr) {
		$keys = array_keys($arr);
		$vals = array_values($arr);

		// remove from beginning
		$vals = $this->rmPunctSeq($vals);
		$diff = count($keys) - count($vals);
		$keys = array_slice($keys, $diff);

		// remove from end
		$revs = array_reverse($vals);
		$vals = array_reverse($this->rmPunctSeq($revs));
		$diff = count($keys) - count($vals);
		if ($diff == 0) {
			return array_combine($keys, $vals);
		}
		else{
			$keys = array_slice($keys, 0, -$diff);
			return array_combine($keys, $vals);
		}
	}

	/**
	 * Remove punctuation from array sequentially
	 *
	 * @param  array $arr
	 * @return array
	 */
	private function rmPunctSeq($arr) {

		if (empty($arr)) {
			return $arr;
		}

		while ($this->isPunct($arr[0])) {
			$arr = array_slice($arr, 1);
		}

		return $arr;
	}

	/**
	 * Remove punctuation from array
	 *
	 * @param  array $arr
	 * @return array $out
	 */
	private function rmPunct($arr) {
		$out = array();
		foreach ($arr as $w) {
			if (!$this->isPunct($w)) {
				$out[] = $w;
			}
		}
		return $out;
	}

	/**
	 * Checks if string is a punctuation
	 *
	 * @param  string  $str
	 * @return boolean
	 */
	private function isPunct($str) {
		if ($this->punct !== NULL) {
			return (in_array($str, $this->punct)) ? TRUE : FALSE;
		}
		else {
			return (preg_match('/^[[:punct:]]+$/u', $str)) ? TRUE : FALSE;
		}
	}
}

// Test Cases:
/*
$args = getopt('f:');
$str = '-LRB- " The cat , black-white , is on the mat . " ';
$arr = preg_split('/\s/u', $str, -1, PREG_SPLIT_NO_EMPTY);
$PRM = new RmPunct($args['f']);

echo $PRM->removePunctuation($str) . "\n";
echo $PRM->removePunctuation($str, TRUE) . "\n";
print_r($PRM->removePunctuation($arr));
print_r($PRM->removePunctuation($arr, TRUE));
*/

