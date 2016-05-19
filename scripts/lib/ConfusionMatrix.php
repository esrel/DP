<?php
/**
 * Set of functions for Confusion Matrix based evaluation
 *
 * Confusion Matrix:
 *
 *      |    REF    |
 *      |  1  |  0  |
 * -----+-----+-----+-----
 * H  1 |  TP |  FP |  HP
 * Y ---+-----+-----+-----
 * P  0 |  FN |  TN |  HN
 * -----+-----+-----+-----
 *      |  RP |  RN |  TT
 *
 * FP -- Type I  Error
 * FN -- Type II Error
 *
 * Metrics:
 *
 * PPV	: Positive Predictive Value | Precision [P]
 * P	= TP / HP	= TP / (TP + FP)
 *
 * NPV	: Negative Predictive Value
 * NPV	= FN / HN	= TN / (TN + FN)
 *
 * TPR	: True Positive Rate | Sensitivity| Recall [R]
 * R	= TP / RP	= TP / (TP + FN)
 *
 * TNR	: True Negative Rate | Specificity
 * TNR	= TN / RN	= TN / (TN + FP)
 *
 * FPR	: False Positive Rate | Fall-out
 * FPR	= FP / RN	= FP / (FP + TN)
 *
 * FNR	: False Negative Rate | Miss Rate
 * FNR	= FN / PR	= FN / (FN + TP)
 *
 * FDR	: False Discovery Rate
 * FDR	= 1 - PPV	= FP / (FP + TP)
 *
 * ACC	: Accuracy
 * ACC	= (TP + TN) / (RP + RN) = (TP + TN) / TT
 *
 * * * * *
 * Composites :
 *
 * FM	: F-Measure
 * F1	= (2 * P * R) / (P + R)
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class ConfusionMatrix  {

	// Zero Confusion Matrix array
	private $zcm = array('TP' => 0, 'FP' => 0, 'FN' => 0, 'TN' => 0);

	/*****
	 * Matrix Functions
	 */

	/**
	 * Build Confusion Matrix
	 * @param  array $ref
	 * @param  array $hyp
	 * @return array $matrix
	 */
	private function buildMatrix($ref, $hyp) {
		$matrix = array();
		for ($i = 0; $i < count($ref); $i++) {
			if (isset($matrix[$ref[$i]][$hyp[$i]])) {
				$matrix[$ref[$i]][$hyp[$i]]++;
			}
			else {
				$matrix[$ref[$i]][$hyp[$i]] = 1;
			}
		}
		return $matrix;
	}

	/**
	 * Compute TP, FP, FN, TN
	 * @param array $ref	references
	 * @param array $hyp	hypotheses
	 */
	public function computeConfMatrix($ref, $hyp) {
		$matrix  = $this->buildMatrix($ref, $hyp);
		$total   = count($ref);
		$classes = array_unique(array_keys($matrix), SORT_NATURAL);
		// class-wise confusion matrices
		$CCM     = array_combine(
					$classes,
					array_fill(0, count($classes), $this->zcm));

		foreach ($classes as $class) {
			foreach ($matrix[$class] as $k => $v) {
				if ($k == $class) {
					$CCM[$class]['TP'] += $v;
					// TN for others
					foreach ($classes as $c) {
						if ($class != $c) {
							$CCM[$c]['TN'] += $v;
						}
					}
				}
				else {
					$CCM[$class]['FN'] += $v;
					$CCM[$k]['FP']     += $v;
					// TN for others
					foreach ($classes as $c) {
						if ($class != $c && $k != $c) {
							$CCM[$c]['TN'] += $v;
						}
					}
				}
			}
		}
		return $CCM;
	}

	/**
	 * Compute Global Confusion Matrix (micros)
	 * @param  array $matrix output of computeConfMatrix()
	 * @return array $CM
	 */
	public function computeGlobalMatrix($matrix) {
		$CM = $this->zcm;
		foreach ($CM as $k => $v) {
			$CM[$k] = array_sum(array_column($matrix, $k));
		}
		return $CM;
	}

	/*****
	 * Metric Functions
	 */

	/**
	 * Calculates Positive Predictive Value | Precision : TP / (TP + FP)
	 * @param 	array $matrix
	 * @return 	float
	 */
	public function positivePredictiveValue($matrix) {
		if ($matrix['TP'] == 0) {
			return 0;
		}

		return $matrix['TP'] / ($matrix['TP'] + $matrix['FP']);
	}

	/**
	 * Calculates Negative Predictive Value : TN / (TN + FN)
	 * @param 	array $matrix
	 * @return 	float
	 */
	public function negativePredictiveValue($matrix) {
		if ($matrix['TN'] == 0) {
			return 0;
		}

		return $matrix['TN'] / ($matrix['TN'] + $matrix['FN']);
	}

	/**
	 * Calculates True Positive Rate | Sensitivity | Recall : TP / (TP + FN)
	 * @param 	array $matrix
	 * @return 	float
	 */
	public function truePositiveRate($matrix) {
		if ($matrix['TP'] == 0) {
			return 0;
		}

		return $matrix['TP'] / ($matrix['TP'] + $matrix['FN']);
	}

	/**
	 * Calculates True Negative Rate | Specificity : TN / (TN + FP)
	 * @param 	array $matrix
	 * @return 	float
	 */
	public function trueNegativeRate($matrix) {
		if ($matrix['TN'] == 0) {
			return 0;
		}

		return $matrix['TN'] / ($matrix['TN'] + $matrix['FP']);
	}

	/**
	 * Calculates Accuracy : (TP + TN) / (TP + TN + FP + FN)
	 * @param 	array $matrix
	 * @return 	float
	 */
	public function accuracy($matrix) {
		if ($matrix['TP'] == 0 && $matrix['TN']) {
			return 0;
		}

		return ($matrix['TP'] + $matrix['TN'])
				/ ($matrix['TP'] + $matrix['TN'] + $matrix['FP'] + $matrix['FN']);
	}

	/*****
	 * F-Measures
	 */

	/**
	 * F-Measure as F_Beta = ((1 + Beta^2) * TP) / ((1 + Beta^2) * TP + Beta^2 * FN + FP)
	 * @param 	array $matrix
	 * @param   float $beta
	 * @return 	float
	 */
	public function fMeasureMatrix($matrix, $beta = 1) {
		if ($matrix['TP'] == 0) {
			return 0;
		}

		if ($beta == 1) {
			// use (2 * TP) / (2 * TP + FN + FP)
			return 	(2 * $matrix['TP']) /
					(2 * $matrix['TP'] + $matrix['FN'] + $matrix['FP']);
		}
		else {
			$b2 = pow($beta, 2);
			$w  = 1 + $b2;
			return 	((1 + pow($beta, 2)) * $matrix['TP'])
						/(	(1 + pow($beta, 2)) * $matrix['TP']
							+ pow($beta, 2) * $matrix['FN']
							+ $matrix['FP']);
		}
	}


	/**
	 * Calculates F-Measure as ((1 + Beta^2) * P * R) / (Beta^2 * P + R)
	 * @param 	float $p	precision
	 * @param   float $r	recall
	 * @param   float $beta
	 * @return 	float
	 */
	public function fMeasure($p, $r, $beta = 1) {
		if ($beta == 1) {
			return (2 * $p * $r) / ($p + $r);
		}
		else {
			return ((1 + pow($beta, 2)) * $p * $r) / (pow($beta, 2) * $p + $r);
		}
	}

	/*****
	 * Aliases
	 */
	public function precision($matrix) {
		return $this->positivePredictiveValue($matrix);
	}

	public function recall($matrix) {
		return $this->truePositiveRate($matrix);
	}

	public function sensivity($matrix) {
		return $this->truePositiveRate($matrix);
	}

	public function specificity($matrix) {
		return $this->trueNegativeRate($matrix);
	}

	/*****
	 * Composites
	 */

	/**
	 * Calculate and return Precision, Recall & F1
	 * @param 	array $matrix
	 * @return 	array of floats
	 */
	public function getPRF($matrix) {
		if ($matrix['TP'] == 0) {
			return array('p' => 0, 'r' => 0, 'f' => 0);
		}

		$p = $this->precision($matrix);
		$r = $this->recall($matrix);
		$f = $this->fMeasure($p, $r);

		return array('p' => $p, 'r' => $r, 'f' => $f);
	}

	/**
	 * Pack Confusion Matrix
	 * @param 	int $TP	True  Positive count
	 * @param 	int $FP	False Positive count
	 * @param 	int $FN	False Negative count
	 * @param 	int $TN	True  Negative count
	 * @return 	array of ints
	 */
	public function packMatrix($TP, $FP, $FN, $TN) {
		return array(
				'TP' => $TP,
				'FP' => $FP,
				'FN' => $FN,
				'TN' => $TN
			);
	}

}
//======================================================================
// Example Usage
//======================================================================
/*
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('h:r:');

$CM = new ConfusionMatrix();

if (isset($args['h']) && isset($args['r'])) {
	$hyp = array_map('trim', file($args['h']));
	$ref = array_map('trim', file($args['r']));
}
else {
	// Test Case
	$ref = array('1','1','1','1','1','0','0','0','0','0');
	$hyp = array('1','0','1','0','1','0','1','0','0','0');
}
// class-level matrix
$cmatrix = $CM->computeConfMatrix($ref, $hyp);
// global matrix
$gmatrix = $CM->computeGlobalMatrix($cmatrix);

ksort($cmatrix);

foreach ($cmatrix as $c => $m) {
	$prf = $CM->getPRF($m);
	echo $c . "\t";
	echo implode("\t", array_map('number_format', $prf, array_fill(0, 3, 3)));
	echo "\n";
}
echo '---' . "\n";
$gprf = $CM->getPRF($gmatrix);
echo '*' . "\t";
echo implode("\t", array_map('number_format', $gprf, array_fill(0, 3, 3)));
echo "\n";
*/
