<?php
/**
 * Aligns array of tokens to the raw string
 * 
 * @author 	Evgeny A. Stepanov
 * @e-mail 	stepanov.evgeny.a@gmail.com
 * @version	0.1
 * @date	2013-07-06
 */
class TokenIndexer {
	
	private $encoding;
	private $s_regex;
	
	function __construct() {
		$this->encoding = 'UTF-8';
		$this->s_regex  = '[[:space:]]';
	}
	
	/**
	 * Aligns array of tokens to raw string:
	 * appends raw string character indices to tokens
	 * 
	 * e.g. array("The", "cat", ",") is mapped to "The cat," as
	 * array(
	 * 	[0] array(
	 * 		['tok'] => "The",		// surface string (from array)
	 * 		['b']	=> 0,			// beginning index
	 * 		['e'] 	=> 3,			// end index
	 * 	), 
	 * 	[1] array("cat", 5, 8 ),
	 * 	[2] array( "," , 9, 10),
	 * );
	 * 
	 * @param 	array 	$arr	array of tokens
	 * @param 	string	$str	string
	 * @return 	array	$out	array of indexed tokens
	 */
	public function indexArr2Str($arr, $str) {
		mb_internal_encoding($this->encoding);

		$arr_flt = $this->flattenArray($arr);
		
		if (!$this->checkStrEquality($arr_flt, $str)) {
			die('Mismatch: Array != String ' . "\n");
		}
		
		$ind_arr = $this->indexTokens2Str($arr_flt, $str);
		$out     = $this->indexArr2IndArr($arr, $ind_arr);
		
		return $out;
	}
	
	/**
	 * Indexes flat array of tokens to string
	 * @param 	array 	$arr	array of tokens
	 * @param 	string	$str	string
	 * @return 	array	$out	array of indexed tokens
	 */
	private function indexTokens2Str($arr, $str) {
		
		mb_internal_encoding($this->encoding);
		
		$out 	= array();
		$bind	= 0;
		
		foreach ($arr as $k => $tok) {
			$tok = trim($tok);
			$out[$k]['tok'] = $tok;
			
			while (preg_match(	'/' . $this->s_regex . '/u', 
								mb_substr($str, $bind, 1))) {
				
				$bind += mb_strlen(mb_substr($str, $bind, 1));
			}

			if (mb_substr($str, $bind, mb_strlen($tok)) === $tok) {

				$out[$k]['b'] = $bind;
				$out[$k]['e'] = $bind + mb_strlen($tok);

				$bind += mb_strlen($tok);
			
			}
			// special case: token is 'space-reduced' from doc
			elseif (	preg_match('/' . $this->s_regex . '/u', mb_substr($str, $bind, mb_strlen($tok)))
					&& !preg_match('/' . $this->s_regex . '/u', $tok)) {
				$i = 1;
				while (mb_strlen(
							preg_replace('/' . $this->s_regex . '/u', '', 
								mb_substr($str, $bind, mb_strlen($tok) + $i)
								)
							) < mb_strlen($tok)
					) {
					$i++;
				}

				if (preg_replace('/' . $this->s_regex . '/u', '', 
						mb_substr($str, $bind, mb_strlen($tok) + $i)) === $tok) {
				
					$out[$k]['b'] = $bind;
					$out[$k]['e'] = $bind + mb_strlen($tok) + $i;
				
					$bind = $bind + mb_strlen($tok) + $i;
				}
				else {
					echo $tok . '|' . preg_replace('/' . $this->s_regex . '/u', '', 
						mb_substr($str, $bind, mb_strlen($tok) + $i));
					die('Error!' . "\n");
				}
			}
			else {
				echo $tok . '|' . mb_substr($str, $bind, mb_strlen($tok)) . "\n";
				die('Error!' . "\n");
			}
		}
		
		return $out;
	}
	
	/**
	 * Check if array and raw string are equal character-wise
	 * @param array  $arr	token array
	 * @param string $raw	raw string
	 * 
	 * @return bool
	 */
	private function checkStrEquality($arr, $raw) {
		
		$raw_str = $this->rmWhiteSpace($raw);
		$arr_flt = $this->flattenArray($arr);
		$arr_str = $this->rmWhiteSpace(implode('', $arr_flt)); 
		
		if ($arr_str === $raw_str) {
			return TRUE;
		}
		else {
			echo $raw_str . "\n";
			echo $arr_str . "\n";
			return FALSE;
		}
		
	}
	

	
	/**
	 * Propagates index to original array
	 * @param  array $arr
	 * @param  array $ind_arr
	 * @return array $out
	 */
	private function indexArr2IndArr($arr, $ind_arr, &$i = 0) {
		
		$out = array();
		
		foreach ($arr as $k => $e) {
			if (is_array($e)) {
				$out[$k] = $this->indexArr2IndArr($e, $ind_arr, $i);
			}
			else {
				if ($e === $ind_arr[$i]['tok']) {
					$out[$k] = $ind_arr[$i];
					$i++;
				}
				else {
					$z = array();
					foreach ($ind_arr as $e) {
						$z[] = $e['tok'];
					}
					print_r($z);
					die('Error' . "\n" .
						implode('',$arr) . "\n" .
						implode('',$z) . "\n"
					);
				}
			}
		}
		
		return $out;
	}
	
	/**
	 * recurisively flatten multidimensional array
	 * @param 	array $arr	multidimensional array to flatten
	 * @return 	array $out	flat array
	 */
	private function flattenArray($arr) {
	
		$out = array();
	
		foreach ($arr as $e) {
			if (is_array($e)) {
				$out = array_merge($out, $this->flattenArray($e));
			}
			else {
				$out[] = $e;
			}
		}
	
		return $out;
	}
	
	/**
	 * Remove White Space from a string
	 * @param 	string $str
	 * @return 	string
	 */
	private function rmWhiteSpace($str) {
		return preg_replace('/' . $this->s_regex . '/u', '', $str);
	}
}