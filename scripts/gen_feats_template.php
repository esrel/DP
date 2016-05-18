<?php
/***
 * Generate features using CoNLL format data & CRF++ like template
 *
 * -t template file
 * -d data     file
 */
require 'ConllReader.php';
require 'CrfTemplateParser.php';

error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('t:d:');

$glue = '+'; // glue for n-gram like features

$CR = new ConllReader();
$TP = new CrfTemplateParser();

$data = $CR->conllRead($args['d']);
$TP->parseTemplate($args['t']);

$feats = $TP->generateFeatures($data, $glue);
$CR->conllWrite($feats);
