#!/bin/bash

# raw text to json for discourse parsing

###
# PATHS:
# Berkeley Parser command
BP='java -jar bin/BerkeleyParser-1.7.jar -gr bin/eng_sm6.gr'
# Stanford constituency to dependency conversion command
SC='java -cp bin/stanford-parser.jar edu.stanford.nlp.trees.EnglishGrammaticalStructure -basic -treeFile'
# Tokenization command
TK='java -cp bin/stanford-parser.jar edu.stanford.nlp.process.DocumentPreprocessor'

sdir='scripts'

raw=$1 #raw text file
out=tmp

$TK $raw > $out.tok
$BP < $out.tok > $out.ptree
$SC $out.ptree > $out.dep

php $sdir/txt2json.php -r $raw -t $out.tok -p $out.ptree -d $out.dep > pdtb-parses.json