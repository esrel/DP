<?php
/***
 * Variour syntactic constituency tree methods/features
 *
 * @author Evgeny A. Stepanov
 * @e-mail stepanov.evgeny.a@gmail.com
 */
require_once 'TreePath.php';

class SyntacticTreeFeatures extends TreePath {

	/**
	 * Get nodes using string/array of consecutive terminal words
	 *
	 * @param  mixed $input
	 * @param  array $ids   // ids of the nodes to get
	 * @return array $nodes
	 */
	public function getNodes($input, $ids = NULL) {
		$nodes = array();

		if (is_string($input)) {
			$iwords = preg_split('/\s/u', $input, -1, PREG_SPLIT_NO_EMPTY);
		}
		elseif (is_array($input)) {
			$iwords = $input;
		}
		else {
			die('ERROR: Input has to be string or array!' . "\n");
		}

		if ($ids !== NULL) {
			$subs = array($ids);
		}
		else {
			// Check if input is a substring of tree words
			$twords = $this->getTerminalWords();
			$tstr   = implode(' ', $twords);
			$istr   = implode(' ', $iwords);

			if (preg_match('/'. preg_quote($istr) . '/u', $tstr)) {
				$subs   = $this->getSubArray($twords, $iwords);
			}
			else {
				$subs = array();
			}
		}

		// return every matching sub-array of nodes
		$tnodes = $this->getTerminals();

		foreach ($subs as $sub) {
			$int = array();
			foreach ($sub as $id) {
				if (isset($tnodes[$id])) {
					$int[$id] = $tnodes[$id];
				}
			}
			$nodes[] = $int;
		}

		return $nodes;
	}

	/**
	 * Get the node that is the lowest common parent
	 *
	 *   parent may have bigger span
	 *
	 * @param  array $nodes  array of terminal nodes
	 * @param  bool  $strict whether parent should cover span exactly
	 * @return obj   $pnode
	 */
	public function getCommonParent($nodes, $strict = FALSE) {

		if (empty($nodes)) {
			return NULL;
		}

		$nodes = array_values($nodes);

		if (count($nodes) == 1) {
			return $nodes[0];
		}
		else {
			$paths  = array_map(array($this, 'getRootPath'), $nodes);
			$spaths = array();
			foreach ($paths as $path) {
				$spaths[] = array_map('serialize', $path);
			}

			$ipath = call_user_func_array('array_intersect', $spaths);
			$cpath = array_map('unserialize', $ipath);
			$ind   = count($cpath) - 1;
			$pnode = (isset($cpath[$ind])) ? $cpath[$ind] : NULL;

			if ($strict && $pnode !== NULL) {
				$span = array_values($this->getTerminals($pnode));
				if ($span !== $nodes) {
					$pnode = NULL;
				}
			}

			return $pnode;
		}
	}

	/**
	 * Get the node that is the highest node to the current
	 *
	 * @param  obj $node
	 * @return obj $node
	 */
	public function getHighestSelfNode($node) {
		while (isset($node->parent)
				&& count($node->parent->children) == 1) {
			$node = $node->parent;
		}
		return $node;
	}

	/**
	 * Get sibling of a node to the left or right ($dir)
	 *
	 * @param  obj    $node
	 * @param  string $dir  [left|right]
	 * @return obj    $sib
	 */
	private function getSiblingNode($node, $dir) {
		$sib  = NULL;

		if ($node == NULL) {
			return $sib;
		}

		$sibs = $this->getSiblings($node);
		if ($sibs) {
			$nkey = array_search($node, $sibs);
			$sibl = (isset($sibs[$nkey - 1])) ? $sibs[$nkey - 1] : NULL;
			$sibr = (isset($sibs[$nkey + 1])) ? $sibs[$nkey + 1] : NULL;

			if ($dir == 'left') {
				$sib = $sibl;
			}
			elseif ($dir == 'right') {
				$sib = $sibr;
			}

			return $sib;
		}
		else {
			return NULL;
		}
	}

	/**
	 * Get sibling of a node to the left
	 *
	 * @param  obj    $node
	 * @return obj
	 */
	public function getSiblingLeft($node) {
		return $this->getSiblingNode($node, 'left');
	}

	/**
	 * Get sibling of a node to the right
	 *
	 * @param  obj    $node
	 * @return obj
	 */
	public function getSiblingRight($node) {
		return $this->getSiblingNode($node, 'right');
	}

	/**
	 * Get indices from $arr that correspond to $sub
	 *
	 * @param  array $arr
	 * @param  array $sub
	 * @return array $out
	 */
	private function getSubArray($arr, $sub) {
		$out = array();
		$tmp = array();
		for ($i=0,$j=0; $i<count($arr);) {
			if ($arr[$i] == $sub[$j]) {
				$tmp[$i] = $arr[$i];
				$i++;
				$j++;
			}
			else {
				if (!empty($tmp) && array_values($tmp) == $sub) {
					$out[] = array_keys($tmp);
				}
				$tmp = array();
				$j = 0;
				$i++;
			}
		}
		return $out;
	}
}

// Test Cases:
/*
$parse = "( (S (NP (NP (JJ Influential) (NNS members)) (PP (IN of) (NP (DT the) (NNP House) (NNPS Ways) (CC and) (NNPS Means) (NNP Committee)))) (VP (VBD introduced) (NP (NP (NN legislation)) (SBAR (WHNP (WDT that)) (S (VP (MD would) (VP (VB restrict) (SBAR (WHADVP (WRB how)) (S (NP (DT the) (JJ new) (JJ savings-and-loan) (NN bailout) (NN agency)) (VP (MD can) (VP (VB raise) (NP (NN capital))))))))))) (, ,) (S (VP (VBG creating) (NP (NP (DT another) (JJ potential) (NN obstacle)) (PP (TO to) (NP (NP (NP (DT the) (NN government) (POS 's)) (NN sale)) (PP (IN of) (NP (JJ sick) (NNS thrifts))))))))) (. .)) )";
$STF   = new SyntacticTreeFeatures($parse);
//$input = 'and';
//$input = 'Influential members';
$input = 'that';
echo '* INPUT: ' . $input . "\n";

$nodes = $STF->getNodes($input);
echo 'NUMBER of MATCHES: ' . count($nodes) . "\n";

foreach ($nodes as $k => $arr) {
	$lcp = $STF->getCommonParent($arr);
	$hcp = $STF->getHighestSelfNode($lcp);
	$sl  = $STF->getSiblingLeft($hcp);
	$sr  = $STF->getSiblingRight($hcp);

	echo 'LCP: ' . $lcp->tag . "\n";
	echo 'HCP: ' . $hcp->tag . "\n";
	echo 'Left S: ';
	echo ($sl === NULL) ? 'NULL' : $sl->tag;
	echo "\n";
	echo 'Right S: ';
	echo ($sr === NULL) ? 'NULL' : $sr->tag;
	echo "\n";
}
*/

