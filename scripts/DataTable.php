<?php
/***
 * Class to print results table
 */
class DataTable {

	private $rsep = "\n"; // row    separator
	private $csep = "\t"; // column separator
	private $prec = 4;    // precision to use
	private $max_str;     // maximum length of string for padding

	/**
	 * Constructor & set parameters
	 * @param int    $prec
	 * @param string $csep
	 * @param string $rsep
	 */
	public function __construct($prec = 4, $csep = "\t", $rsep = "\n") {
		$this->prec = $prec;
		$this->csep = $csep;
		$this->rsep = $rsep;
	}

	/**
	 * Rounding & padding of numbers to set decimal point
	 * @param mixed $num
	 */
	private function ppnum($num) {
		$p = $this->prec;
		if (is_float($num)) {
			return number_format(round($num, $p), $p);
		}
		else {
			return $num;
		}
	}

	/**
	 * Print Table
	 *
	 * @param array $arr
	 * @param array $rowh   // row    headers
	 * @param array $colh   // column headers
	 * @param string $title // table title
	 */
	public function printTable( $data,
								$rowh  = NULL,
								$colh  = NULL,
								$title = NULL) {

		$data = array_values($data);

		if ($rowh) {
			$rowh = $this->spacePad($rowh);
		}

		// print title, if set
		if ($title) {
			echo str_pad($title, $this->max_str);
		}
		else {
			echo str_pad(' ', $this->max_str);
		}
		echo $this->csep;

		// print column headers, if set
		if ($colh) {
			echo implode($this->csep, $colh);
		}
		echo $this->rsep;

		// print data
		foreach ($data as $k => $row) {
			// apply precision
			$pprow = array_map(array($this, 'ppnum'), $row);

			// print row header, if set
			if ($rowh) {
				echo $rowh[$k];
				echo $this->csep;
			}

			echo implode($this->csep, $pprow);
			echo $this->rsep;

		}
		echo $this->rsep;
	}

	/**
	 * Space pad headers
	 *
	 * @param  array $arr
	 * @return array $out
	 */
	private function spacePad($arr) {
		$len_arr = array_map('strlen', $arr);
		$max = max($len_arr);
		$this->max_str = $max;
		$out = array();
		foreach ($arr as $e) {
			$out[] = str_pad($e, $max);
		}
		return $out;
	}

}
