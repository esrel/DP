<?php
/**
 * Class for MPQA Lexicon
*
* Input: MPQA.json
*/
class MpqaSubjLex {

	private $lexicon;
	// polarity reversing tokens
	private $negation = array('no', 'not', "n't", 'never');

	private $opposite = array(
			'negative' => 'positive',
			'positive' => 'negative',
			'neutral'  => 'neutral',
			'both'     => 'both',
		);


	/**
	 * Json 2 array
	 * @param file $json
	 */
	function __construct($json) {
		$str = trim(file_get_contents($json));
		$this->lexicon = json_decode($str, TRUE);
	}

	/**
	 * Returns polarity of a word
	 * @param  string $str
	 * @return string or boolean
	 */
	public function getPolarity($str) {
		$str = strtoupper($str);

		if (isset($this->lexicon[$str])) {
			return $this->lexicon[$str][0];
		}
		else {
			return NULL;
		}
	}

	/**
	 * Returns reversed polarity of a word
	 * @param  string $str
	 * @return string or boolean
	 */
	public function changePolarity($str) {
		$str = strtoupper($str);

		if (isset($this->lexicon[$str])) {
			return $this->opposite[$this->lexicon[$str][0]];
		}
		else {
			return NULL;
		}
	}

	/**
	 * Returns subjectivity of a word
	 * @param  string $str
	 * @return string or boolean
	 */
	public function getSubjectivity($str) {
		$str = strtoupper($str);

		if (isset($this->lexicon[$str])) {
			return $this->lexicon[$str][1];
		}
		else {
			return NULL;
		}
	}

	/**
	 * Check if token is polarity reversing
	 * @param  string $str
	 * @return bool
	 */
	public function isNegation($str) {
		if (in_array(strtolower($str), $this->negation)) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
}

