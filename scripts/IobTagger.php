<?php
/***
 * Tags an array with IOB(E) tags
 *
 * One of IOB, IOBE
 *
 * Input & Output formats (default -- token array):
 *
 * token array: $arr[$tokID]  = array($label, $tag);
 * span  array: $arr[$spanID] = array($label, array($tokIDs));
 * label array: $arr          = array($labels);
 *
 * Functions are defined for:
 *    (1) segment-level  arrays [default]
 *    (2) document-level arrays: array of segment-level arrays
 *
 * @author 	: Evgeny A. Stepanov
 * @e-mail	: stepanov.evgeny.a@gmail.com
 * @date	: 2013-09-19
 * @version : 0.1 from 2013-09-18
 */
class IobTagger {

	private $tag_sets = array('IOB', 'IOBE');
	private $tag_set  = 'IOB';    // default tag set
	private $tag_out  = 'O';      // default out of chunk tag
	private $contig   = TRUE;     // disallow non-contiguous spans

	/**
	 * Sets IOB(E) tag set to use
	 * @param string $tag_set
	 */
	function __construct($tag_set = 'IOB', $contig = TRUE) {
		$this->setTagSet($tag_set);
		$this->setContiguousness($contig);
	}

	/**
	 * Tags an array with IOB(E) tags
	 *
	 * @param  string $label   label to use
	 * @param  array  $arr     array to tag (token IDs)
	 * @param  string $tag_set tag set to use  [IOB|IOBE]
	 * @return array
	 */
	public function tagSpan($label, $arr, $tag_set = 'IOB') {

		// sort token IDs
		sort($arr, SORT_NUMERIC);

		// create label array
		$lbl_arr = array_fill(0, count($arr), $label);

		// create tag array
		if ($label == $this->tag_out) {
			$tag_arr = array_fill(0, count($arr), NULL);
		}
		else {
			$tag_arr = array_fill(0, count($arr), 'I');
			$tag_arr[count($arr)-1] = ($tag_set == 'IOBE') ? 'E' : 'I';
			$tag_arr[0] = 'B';
		}

		// join labels and tags
		$out = array_map(array($this, 'tuple'), $lbl_arr, $tag_arr);

		// use tokenIDs as keys & return
		return array_combine($arr, $out);
	}

	/**
	 * Default behavior for re-tagging
	 *
	 * @param  array  $arr
	 * @param  string $tag_set
	 * @return array
	 */
	public function reTag($arr, $tag_set = 'IOB') {
		return $this->reTagSeg($arr, $tag_set);
	}

	/**
	 * IOB-re-tag documnet:
	 *    useful to convert IOB & IOBE to each other
	 *
	 * @param array  $arr
	 * @param string $tag_set
	 *
	 * @return array $out
	 */
	public function reTagDoc($arr, $tag_set = 'IOB') {
		$out = array();
		foreach ($arr as $segID => $seg) {
			$out[$segID] = $this->reTagSeg($seg, $tag_set);
		}
		return $out;
	}

	/**
	 * IOB-re-tag segment
	 *    useful to convert IOB & IOBE to each other or to fix tags
	 *
	 * @param array  $arr     token array
	 * @param string $tag_set token array
	 *
	 * @return array
	 */
	public function reTagSeg($arr, $tag_set = 'IOB') {
		$out   = array();
		$spans = $this->token2spanSeg($arr, TRUE);
		foreach ($spans as $spanID => $span) {
			list($lbl, $tok_arr) = $span;
			$out += $this->tagSpan($lbl, $tok_arr, $tag_set);
		}
		ksort($out);
		return $out;
	}

	/* Conversion Functions */

	/**
	 * Default behavior for span2token functions
	 * @param  array   $arr      span array
	 * @param  boolean $fill_out
	 * @return array             token array
	 */
	public function span2token($arr, $fill_out = FALSE) {
		return $this->span2tokenSeg($arr, $fill_out);
	}

	/**
	 * Convert span-level array to token-level array: document-level
	 *    from $arr[$segID][$spanID] = array($label, array($tokIDs));
	 *    to   $arr[$segID][$tokID]  = array($label, $tag);
	 *
	 * @param  array   $arr span  array
	 * @param  boolean $fill_out whether to fill segment gaps with 'O'
	 * @return array   $out token array
	 */
	public function span2tokenDoc($arr, $fill_out = FALSE) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			$out[$segID] = $this->span2tokenSeg($seg, $fill_out);
		}
		return $out;
	}

	/**
	 * Convert span-level array to token-level array: segment-level
	 *    from $arr[$spanID] = array($label, array($tokIDs));
	 *    to   $arr[$tokID]  = array($label, $tag);
	 *
	 * @param  array   $arr span  span array
	 * @param  boolean $fill_out whether to fill segment gaps with 'O'
	 * @return array   $out token token array
	 */
	public function span2tokenSeg($arr, $fill_out = FALSE) {
		$out = array();

		// Fill span with out-of-chunk labels
		if ($fill_out) {
			$tok_arr = call_user_func_array('array_merge',
											array_column($arr, 1));
			$out_lbl = array($this->tag_out);
			$range   = range(min($tok_arr), max($tok_arr));
			$lbl_arr = array_fill(0, count($range), $out_lbl);
			$out     = array_combine($range, $lbl_arr);
		}

		foreach ($arr as $span) {
			$out_arr = call_user_func_array(array($this, 'tagSpan'),
											$span);
			$out = $out_arr + $out;
		}

		ksort($out);
		return $out;
	}

	/**
	 * Default behavior for token2span functions
	 * @param  array   $arr
	 * @param  boolean $keep_out
	 * @return array
	 */
	public function token2span($arr, $keep_out = FALSE) {
		return $this->token2spanSeg($arr, $keep_out);
	}

	/**
	 * Convert token-level array to span-level array: document array
	 *    from $arr[$segID][$tokID]  = array($label, $tag)
	 *    to   $arr[$segID][$spanID] = array($label, array($tokIDs));
	 *
	 * @param  array   $arr      token array
	 * @param  boolean $keep_out whether to keep out-of-chunk 'labels'
	 * @return array   $out      span array
	 */
	public function token2spanDoc($arr, $keep_out = FALSE) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			$out[$segID] = $this->token2spanSeg($seg, $keep_out);
		}
		return $out;
	}

	/**
	 * Convert token-level array to span-level array: segment array
	 *    from $arr[$tokID]  = array($label, $tag)
	 *    to   $arr[$spanID] = array($label, array($tokIDs));
	 *
	 * @param  array   $arr      token array
	 * @param  boolean $keep_out whether to keep out-of-chunk 'labels'
	 * @return array   $out      span array
	 */
	public function token2spanSeg($arr, $keep_out = FALSE) {
		$out   = array();
		$ncs   = array(); // non-contiguous spans array
		$ecs   = array(); // 'E' closed spans array
		$segID = 0;
		foreach ($arr as $tokID => $tok) {

			if (count($tok) == 1) {
				$tag = $tok[0];
				$lbl = $tok[0];
			}
			else {
				list($lbl, $tag) = $tok;
			}

			switch ($tag) {
				case 'B': // new
					$segID = (!empty($out)) ? $segID + 1 : $segID;
					$out[$segID] = array($lbl, array($tokID));
				break;

				case 'I': // add
				case 'E':
					if (empty($out)) {
						$out[$segID] = array($lbl, array($tokID));
					}
					else {
						if ($out[$segID][0] != $lbl) { // new chunk
							$segID = (!empty($out)) ? $segID + 1 : $segID;
							$out[$segID] = array($lbl, array($tokID));
							$ncs[] = $segID;
						}
						else {
							$out[$segID][1][] = $tokID;
						}
					}

					if ($tag == 'E') {
						$ecs[] = $segID;
					}
				break;

				default: // 'O'
					$segID = (!empty($out)) ? $segID + 1 : $segID;
					$out[$segID] = array($lbl, array($tokID));
				break;
			}
		}

		if (!$this->contig) {
			$out = $this->joinNonContiguousSpans($out, $ncs, $ecs);
		}

		if (!$keep_out) {
			$out = $this->rmSpanByLabel($out, $this->tag_out);
		}

		return array_values($out);
	}

	/**
	 * Default behavior for getLabels functions: segment
	 * @param  array   $arr      token array
	 * @param  boolean $keep_out
	 * @return array             label array
	 */
	public function getLabels($arr, $keep_out = FALSE) {
		return $this->getLabelsSeg($arr, $keep_out);
	}

	/**
	 * Extract labels from span array: document-level
	 *
	 * @param  array   $arr
	 * @param  boolean $keep_out whether to keep out-of-chunk tag
	 * @return array   $out
	 */
	public function getLabelsDoc($arr, $keep_out = FALSE) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			$out[$segID] = $this->getLabelsSeg($seg, $keep_out);
		}
		return $out;
	}

	/**
	 * Extract labels from span array: segment-level
	 *
	 * @param  array   $arr      token array
	 * @param  boolean $keep_out whether to keep out-of-chunk tag
	 * @return array   $out      label array
	 */
	public function getLabelsSeg($arr, $keep_out = FALSE) {
		$spans  = $this->token2spanSeg($arr, $keep_out);
		$labels = array_column($spans, 0);

		return $labels;
	}

	/**
	 * Default behavior for gap filling functions
	 *
	 * @param  array $arr token array
	 * @return array      token array
	 */
	public function fillGaps($arr) {
		return $this->fillGapsSeg($arr);
	}

	/**
	 * Fill Gaps in Non-Contiguous spans: document
	 *
	 * @param  array $arr token array
	 * @return array $out token array
	 */
	public function fillGapsDoc($arr) {
		$out = array();
		foreach ($arr as $segID => $seg) {
			$out[$segID] = $this->fillGapsSeg($seg);
		}
		return $out;
	}

	/**
	 * Fill Gaps in Non-Contiguous spans: segment
	 *    replaces out-of-chunk tags
	 *
	 * @param  array $arr token array
	 * @return array      token array
	 */
	public function fillGapsSeg($arr) {

		// disable contiguousness
		$this->setContiguousness(FALSE);

		$fspans = array();
		$gspans = $this->token2spanSeg($arr);
		foreach ($gspans as $spanID => $span) {
			list($lbl, $tok_arr) = $span;
			$range = range(min($tok_arr), max($tok_arr));

			if (count($tok_arr) == count($range)) {
				$fspans[$spanID] = $span;
			}
			else {
				// get other spans
				$tspans = array_column($gspans, 1);
				$ospans = call_user_func_array('array_merge',
							array_merge(
								array_slice($tspans, 0, $spanID),
								array_slice($tspans, $spanID + 1)));

				// check intersection of the range with other spans
				$int = array_intersect($range, $ospans);

				// span = range, if no intersection; skip otherwise
				if (empty($int)) {
					$fspans[$spanID] = array($lbl, $range);
				}
				else {
					$fspans[$spanID] = $span;
				}
			}
		}
		return $this->span2tokenSeg($fspans, TRUE);
	}

	/* Out-of-Chunk Functions */

	/**
	 * Remove specific label from the label array
	 *
	 * @param  array  $arr
	 * @param  string $label
	 * @return array  $out
	 */
	public function rmLabel($arr, $label) {
		$out = array();
		foreach ($arr as $e) {
			if ($e != $label) {
				$out[] = $e;
			}
		}
		return $out;
	}

	/* Support Functions */

	/**
	 * Remove spans with specific label
	 *
	 * @param  array  $arr
	 * @param  string $label
	 * @return array
	 */
	public function rmSpanByLabel($arr, $label) {
		$key_arr = array_keys($arr);
		$val_arr = array_column($arr, 0);
		$lbl_arr = array_combine($key_arr, $val_arr);
		$ids_arr = array_keys($lbl_arr, $label);
		$out_arr = array_fill_keys($ids_arr, $label);
		return array_diff_key($arr, $out_arr);
	}

	/**
	 * Join non-contiguous spans
	 *
	 * @param  array $arr array of spans
	 * @param  array $ncs array of non-contiguous span IDs
	 * @param  array $ecs array of 'closed' spans
	 * @return array $arr
	 */
	private function joinNonContiguousSpans($arr, $ncs, $ecs) {
		if (empty($ncs)) {
			return $arr;
		}
		else {
			$out = array();
			$ecs_arr = array_fill_keys($ecs, 1);
			foreach ($ncs as $id) {
				$label = $arr[$id][0];
				$prev  = array_slice($arr, 0, $id, TRUE);
				$diff  = array_diff_key($prev, $ecs_arr);

				$keys  = array_keys($diff);
				$vals  = array_column($diff, 0);
				$lbls  = array_combine($keys, $vals);

				$over  = array_keys($lbls, $label);

				if (!empty($over)) {
					// get span & append
					$last = $over[count($over) - 1];
					$span = array_merge($arr[$last][1], $arr[$id][1]);
					$arr[$last][1] = $span;
					$arr[$id] = array(NULL, NULL);
				}
			}

			// remove extra 'spans'
			foreach ($arr as $k => $e) {
				if (!$e[0]) {
					unset($arr[$k]);
				}
			}

			return $arr;
		}
	}

	/* Setters & Getters */

	/**
	 * Set out tag
	 * @param string $str
	 */
	public function setTagOut($str) {
		$this->tag_out = $str;
	}

	/**
	 * Set default tag set
	 * @param string $str
	 */
	public function setTagSet($str) {
		if (in_array($str, $this->tag_sets)) {
			$this->tag_set = $str;
		}
		else {
			die('Wrong Tag Set!' . "\n");
		}
	}

	/**
	 * Set contiguousness parameter
	 * @param boolean $param
	 */
	public function setContiguousness($param) {
		if (is_bool($param)) {
			$this->contig = $param;
		}
		else {
			die('Use TRUE or FALSE' . "\n");
		}
	}

	/* Utility Functions */
	/**
	 * Makes tuple out of 2 elements & removes NULL for 'O'
	 * @param  mixed $e1
	 * @param  mixed $e2
	 * @return array
	 */
	private function tuple($e1, $e2) {
		return array_filter(array($e1, $e2));
	}
}

// Test Cases
/*
$IT = new IobTagger('IOB');

$span  = array(2, 3, 5);
$label = 'B';
$seg   = array(
	array('A', 'B'),
	array('O'),
	array('B', 'B'),
	array('B', 'I'),
	array('O'),
	array('B', 'I'),
	array('C', 'B'),
	array('O'),
	array('C', 'I')
);

// Function Tests:
echo 'Input:' . "\n";
echo json_encode($seg) . "\n";
echo "\n";
echo 'tagSpan:' . "\n";
// {"2":["B","B"],"3":["B","I"],"5":["B","I"]}
echo '$tag_set = IOB:' . "\n";
echo json_encode($IT->tagSpan($label, $span)) . "\n";
// {"2":["B","B"],"3":["B","I"],"5":["B","E"]}
echo '$tag_set = IOBE:' . "\n";
echo json_encode($IT->tagSpan($label, $span, 'IOBE')) . "\n";
echo "\n";
echo 'token2span:' . "\n";
// [["A",[0]],["B",[2,3]],["B",[5]],["C",[6]],["C",[8]]]
echo '$keep_out = FALSE:' . "\n";
echo json_encode($IT->token2spanSeg($seg)) . "\n";
// [["A",[0]],["O",[1]],["B",[2,3]],["O",[4]],["B",[5]],["C",[6]],["O",[7]],["C",[8]]]
echo '$keep_out = TRUE:' . "\n";
echo json_encode($IT->token2spanSeg($seg, TRUE)) . "\n";
echo "\n";
*/
