<?php
/***
 * Generate features using CoNLL format data & CRF++ like template
 *
 * -t template file
 * -d data     file
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
require 'lib/ConllReader.php';
require 'lib/CrfTemplateParser.php';

// Settings
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

// Arguments
$args = getopt('t:d:');

// Constants
$glue = '+'; // glue for n-gram like features

// Classes
$CR = new ConllReader();
$TP = new CrfTemplateParser();

//----------------------------------------------------------------------
$data = $CR->conllRead($args['d']);
$TP->parseTemplate($args['t']);

$feats = $TP->generateFeatures($data, $glue);
$CR->conllWrite($feats);
