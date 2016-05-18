<?php
/**
 * Class to read/write & manipulate CoNLL format files:
 *   token per-line
 *   empty line separated
 */

require_once 'ArrayUtilities.php';

class ConllReader extends ArrayUtilities {

	private $sep = "\t"; // column  separator [default is TSV]
	private $eos = "\n"; // segment separator

	/**
	 * Constuctor & set column & segment separators
	 *
	 * @param string $sep
	 */
	public function __construct($sep = "\t", $eos = "\n") {
		$this->sep = $sep;
		$this->eos = $eos;
	}

	/**
	 * Read columns into array
	 *
	 * @param  file  $file
	 * @return array $out
	 */
	public function conllRead($file) {
		$out = array();
		$lines_arr = array_map('trim', file($file));
		$segID = 0;
		foreach ($lines_arr as $line) {
			if ($line != '' && $line != $this->eos) {
				$out[$segID][] = explode($this->sep, $line);
			}
			else {
				$segID++;
			}
		}
		return $out;
	}

	/**
	 * Print array in CoNLL format
	 *
	 * @param array $arr
	 */
	public function conllWrite($arr) {

		foreach ($arr as $segID => $seg) {
			foreach ($seg as $tokID => $tok) {
				echo implode($this->sep, $tok) . "\n";
			}
			echo $this->eos;
		}
	}

	/* Column Operations */

	/**
	 * Append column to the end
	 *
	 * @param  array $arr
	 * @param  array $col
	 * @return array
	 */
	public function columnAppend($arr, $col) {
		$out   = array();
		$index = $this->columnCount($arr);
		return $this->columnInsert($arr, $col, $index);
	}

	/**
	 * Insert column to specific position
	 *
	 * @param  array $arr
	 * @param  array $col
	 * @param  int   $index
	 * @return array $out
	 */
	public function columnInsert($arr, $col, $index) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			foreach ($seg as $tokID => $tok) {
				$b = array_slice($tok, 0, $index);
				$e = array_slice($tok, $index);
				$m = array();
				if (is_array($col[$segID][$tokID])) {
					foreach ($col[$segID][$tokID] as $value) {
						$m[] = $value;
					}
				}
				else {
					$m[] = $col[$segID][$tokID];
				}
				$out[$segID][$tokID] = array_merge($b, $m, $e);
			}
		}
		return $out;
	}

	/**
	 * Remove column from specific position
	 *
	 * @param  array $arr
	 * @param  int   $index
	 * @return array $out
	 */
	public function columnRemove($arr, $index) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			foreach ($seg as $tokID => $tok) {
				$b = array_slice($tok, 0, $index);
				$e = array_slice($tok, $index + 1);
				$out[$segID][$tokID] = array_merge($b, $e);
			}
		}
		return $out;
	}

	/**
	 * Get column from specific position
	 *
	 * @param  array $arr
	 * @param  int   $index
	 * @return array $out
	 */
	public function columnGet($arr, $index) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			foreach ($seg as $tokID => $tok) {
				$out[$segID][$tokID] = $tok[$index];
			}
		}
		return $out;
	}

	/**
	 * Get columns from specific positions
	 *
	 * @param  array $arr
	 * @param  int   $begin
	 * @param  int   $end
	 * @return array $out
	 */
	public function columnGetSpan($arr, $begin = 0, $end = NULL) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			foreach ($seg as $tokID => $tok) {
				if ($end) {
					$size = $end - $begin + 1;
					$out[$segID][$tokID] = array_slice($tok, $begin, $size);
				}
				else {
					$out[$segID][$tokID] = array_slice($tok, $begin);
				}
			}
		}
		return $out;
	}

	/**
	 * Get number of columns
	 *
	 * @param  array $arr
	 * @return int   $count
	 */
	public function columnCount($arr) {
		$tok_arr = $this->arrayFlatten($arr);
		$counts  = array_unique(array_map('count', $tok_arr));

		if (count($counts) != 1) {
			die('Column Number Mismatch!' . "\n");
		}
		else {
			$count = $counts[0];
		}
		return $count;
	}

}
