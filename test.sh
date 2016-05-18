#!/bin/bash
#
# CoNLL 2016 Shared Task: Shallow Discourse Parsing
#
# Testing Pipeline
#
#======================================================================#
# Script Arguments: Input & Ouput directories
#======================================================================#

idir=$1 # Input  Directory (with data in JSON format)
odir=$2 # Output Directory

if [ $odir == '' ]
then
	odir='.'
fi

########################################################################
# System Configuration
########################################################################
#======================================================================#
# Data Directories: Corpora, etc.
#======================================================================#
#dat=$idir/relations.json # relations json (annotations)
par=$idir/parses.json    # parses json
raw=$idir/raw            # raw documents directory

#======================================================================#
# Local Directories
#======================================================================#
root='.'             # CHANGE to system directory
bdir=$root/bin       # bin     directory
sdir=$root/scripts   # scripts directory
ddir=$root/data      # data    directory (local)
mdir=$root/models    # models  directory
tdir=$root/templates # CRF templates directory

# date & time
dtd=$(date +%Y%m%dT%H%M%S)

wdir=$root/wdir.$dtd # working directory
fout=$wdir/hyp       # output file prefix

mkdir -p $wdir       # Create Working Directory

#======================================================================#
# Resources (Lexicons, etc.)
#======================================================================#
brown=$ddir/bc3200.txt         # Brown Clusters
mpqal=$ddir/mpqa_subj_05.json  # MPQA lexicon
vnlex=$ddir/VerbNet.json       # VerbNet

cnlex=$ddir/connectives.txt    # List of PDTB connective heads
slist=$ddir/pdtb_senses.txt    # List of PDTB senses (full)
chtbl=$ddir/chars_icsi.txt     # Character replacement table for icsi

#======================================================================#
# 3rd Party Executables & Parameters
#======================================================================#
# CHANGE these for global/local binaries
#----------------------------------------------------------------------#
# ChunkLink script
#----------------------------------------------------------------------#
chunklink="perl $bdir/chunklink.pl -ns"
#----------------------------------------------------------------------#
# TreeTagger setup
#----------------------------------------------------------------------#
ttbin="$bdir/treetagger/bin/tree-tagger"
ttpar="$bdir/treetagger/lib/english.par"
treetagger="$ttbin -token -lemma -no-unknown -eos-tag '<eos>' $ttpar"
#----------------------------------------------------------------------#
# icsiboost
#----------------------------------------------------------------------#
adatst='icsiboost -C --posteriors'
#----------------------------------------------------------------------#
# CRF++
#----------------------------------------------------------------------#
crftst='crf_test'

#======================================================================#
# Models
#======================================================================#
# file names for CRF data/template/model
spans=('CONN' 'SS.A1' 'SS.A2' 'PS.A1' 'PS.A2' 'NE.A1' 'NE.A2')
tasks=('APC' 'CSC' 'RSC')

#----------------------------------------------------------------------#
# Discourse Connective Detection Model
#----------------------------------------------------------------------#
dcdm=$mdir/CONN.model
#----------------------------------------------------------------------#
# Connective Sense Classification Model & Names file
#----------------------------------------------------------------------#
cscn=$mdir/CSC.names
cscm=$mdir/CSC.shyp
#----------------------------------------------------------------------#
# Argument Position Classification Model & Names file
#----------------------------------------------------------------------#
apcn=$mdir/APC.names
apcm=$mdir/APC.shyp
#----------------------------------------------------------------------#
# Argument Span Extraction Models
#----------------------------------------------------------------------#
aseSSA1m=$mdir/SS.A1.model
aseSSA2m=$mdir/SS.A2.model
asePSA1m=$mdir/PS.A1.model
asePSA2m=$mdir/PS.A2.model
aseNEA1m=$mdir/NE.A1.model
aseNEA2m=$mdir/NE.A2.model
#----------------------------------------------------------------------#
# Non-Explicit Relation Sense Classification Model
#----------------------------------------------------------------------#
rscn=$mdir/RSC.names
rscm=$mdir/RSC.shyp

########################################################################
# Data Processing
########################################################################
# Extract Information/Features from provided files
echo
echo '============================================='
echo 'Data Processing'
echo '============================================='
#======================================================================#
# Process parses.json & output: IDs={docID,sentID,tokID}
#  1. $fout.tok.offsets IDs+{doc-level ID,Character Offsets}     (CoNLL)
#  2. $fout.tok         IDs+{token,POS-tag}                      (CoNLL)
#  3. $fout.dep         Dependency parses                        (CoNLL)
#  4. $fout.parse       Constituency parses             (parse-per-line)
#  5. $fout.parse.ids   IDs TSV file {doc,sent} for (4)
#======================================================================#
echo 'Processing parses'
php $sdir/proc_parses.php -p $par -n $fout

#======================================================================#
# Process raw text files & output:
#  1. $fout.par.offsets Paragraph Character Offsets
#======================================================================#
echo 'Processing raw text: paragraph offsets'
arr=($(ls $raw))
for doc in ${arr[@]}
do
	php $sdir/proc_paragraphs.php -r $raw/$doc
done > $fout.par.offsets

#======================================================================#
# Generate adjacent sentence pairs w.r.t. paragraph boundaries & output:
#  1. $fout.sent.pairs  {docID,Arg1sentID,Arg2sentID}
#======================================================================#
echo 'Generating adjacent sentence pairs'
php $sdir/gen_ids_adjacent_pairs.php \
	-p $fout.par.offsets \
    -t $fout.tok.offsets > $fout.sent.pairs

########################################################################
# Feature Extraction/Generation
########################################################################
echo '============================================='
echo 'Feature Extraction/Generation'
echo '============================================='
echo '---------------------------------------------'
echo 'Feature Extraction/Generation: token-level'
echo '---------------------------------------------'
#======================================================================#
# 3rd Party Feature Extraction
#======================================================================#
#----------------------------------------------------------------------#
# ChunkLink: IDs+{IOB-tag,IOB-chain}                             (CoNLL)
#----------------------------------------------------------------------#
echo 'Feature Extraction: IOB (chunklink)'
$chunklink $fout.parse > $fout.clink 2> $fout.chunklink.log
php $sdir/proc_clink.php \
	-t $fout.tok.offsets \
	-f $fout.clink > $fout.tok.feats.iob.tsv

#----------------------------------------------------------------------#
# TreeTagger: IDs+{tok,pos,lemma}                                (CoNLL)
#----------------------------------------------------------------------#
echo 'Feature Extraction: lemmas (TreeTagger)'
cat $fout.tok | cut -f 4 | sed 's/^$/<eos>/g' > $fout.tok.tmp
$treetagger $fout.tok.tmp > $fout.treetagger 2> $fout.treetagger.log
cat $fout.treetagger | sed 's/	*<eos>	*//g' | cut -f 3 > $fout.lem.tmp
paste $fout.tok $fout.lem.tmp > $fout.tok.feats.lemma.tsv

#======================================================================#
# Transformation-based Feature Extraction/Generation
#======================================================================#
#----------------------------------------------------------------------#
# Dependency Features: IDs +                                     (CoNLL)
#  a. Boolean Main Verb (root)
#  b. Dependency Chain
#----------------------------------------------------------------------#
echo 'Feature Generation: Dependency'
php $sdir/gen_feats_dependency.php \
	-f $fout.dep > $fout.tok.feats.dep.tsv

#----------------------------------------------------------------------#
# Syntactic Tree Features                                        (CoNLL)
#----------------------------------------------------------------------#
echo 'Feature Generation: Syntactic Tree'
php $sdir/gen_feats_tree.php \
	-t $fout.tok \
	-p $fout.parse \
	-i $fout.parse.ids > $fout.tok.feats.tree.tsv

#======================================================================#
# Resource-based Feature Extraction/Generation
#======================================================================#
#----------------------------------------------------------------------#
# Brown Clusters: IDs+Brown Cluster ID                           (CoNLL)
#----------------------------------------------------------------------#
echo 'Feature Generation: Brown Clusters'
php $sdir/gen_feats_brown.php \
	-f $fout.tok.feats.lemma.tsv \
	-l $brown > $fout.tok.feats.brown.tsv
#----------------------------------------------------------------------#
# VerbNet: IDs+VerbNet types                                     (CoNLL)
#----------------------------------------------------------------------#
echo 'Feature Generation: VerbNet'
php $sdir/gen_feats_verbnet.php \
	-f $fout.tok.feats.lemma.tsv \
	-l $vnlex > $fout.tok.feats.vn.tsv
#----------------------------------------------------------------------#
# MPQA Lexicon: IDs+{subj,pol}                                   (CoNLL)
#----------------------------------------------------------------------#
echo 'Feature Generation: MPQA'
php $sdir/gen_feats_mpqa.php \
	-f $fout.tok.feats.lemma.tsv \
	-l $mpqal > $fout.tok.feats.mpqa.tsv
#----------------------------------------------------------------------#
# PDTB Connective Heads: IDs+{str,bool}                          (CoNLL)
#----------------------------------------------------------------------#
echo 'Feature Generation: PDTB Connective Head'
php $sdir/gen_feats_conn.php \
	-f $fout.tok \
	-l $cnlex > $fout.tok.feats.conn.tsv

#======================================================================#
# Resource-based Feature Extraction/Generation (sentence-level)
#======================================================================#
echo '---------------------------------------------'
echo 'Feature Extraction/Generation: sentence-level'
echo '---------------------------------------------'
#----------------------------------------------------------------------#
# Dependency: {docID,sentID,root,subj,dobj,iobj}
#----------------------------------------------------------------------#
echo 'Feature Extraction: Dependency IDs'
php $sdir/gen_feats_sent_dependency.php \
	-t $fout.tok \
	-d $fout.dep > $fout.sent.feats.dep.tsv
#----------------------------------------------------------------------#
# VerbNet: IDs+{vn}
#----------------------------------------------------------------------#
echo 'Feature Generation: VerbNet'
php $sdir/gen_feats_sent_verbnet.php \
	-f $fout.tok.feats.vn.tsv \
	-d $fout.sent.feats.dep.tsv > $fout.sent.feats.vn.tsv
#----------------------------------------------------------------------#
# MPQA Lexicon: IDs+{num,pol}
#----------------------------------------------------------------------#
echo 'Feature Generation: MPQA'
php $sdir/gen_feats_sent_mpqa.php \
	-f $fout.tok.feats.mpqa.tsv > $fout.sent.feats.mpqa.tsv

#**********************************************************************#
#*************************** AUTOMATIC MODE ***************************#
#**********************************************************************#
echo
echo '%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%'
echo '%%%%%%%% Discourse Parsing Pipeline %%%%%%%%%'
echo '%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%'
########################################################################
# (1) Discourse Connective Detection
########################################################################
echo '============================================='
echo '(1) Discourse Connective Detection (DCD)'
echo '============================================='
#======================================================================#
# (a) Make data file (CoNLL)
#======================================================================#
echo '(1a) DCD: Generating Data'
# join token level features
#
#  1. token character offsets : 6 : 3 + 3 : all
#  2. tok, pos, lemma         : 6 : 3 + 3 : -3
#  3. iob                     : 5 : 3 + 2 : -3
#  4. dependency features     : 5 : 3 + 2 : -4
#  5. connective head         : 5 : 3 + 2 : -4
#  6. syntactic  tree         : 8 : 3 + 5 : -4
#  7. VerbNet                 : 4 : 3 + 1 : -3

# @EXP: Modify w.r.t. experiments
paste $fout.tok.offsets \
	$fout.tok.feats.lemma.tsv \
	$fout.tok.feats.iob.tsv \
	$fout.tok.feats.dep.tsv \
	$fout.tok.feats.conn.tsv \
	$fout.tok.feats.tree.tsv \
	$fout.tok.feats.vn.tsv |\
	cut -f 1-6,10-12,16-17,22,27,32-35,39 > $fout.tok.feats.tsv

# add relation column (repeat sentence column)
cat $fout.tok.offsets | awk '{OFS="\t"; print $1,$2,$2,$3}' > $fout.ids

php $sdir/gen_data_span.php \
	-f $fout.tok.feats.tsv \
	-l $fout.ids > $fout.CONN.data

#======================================================================#
# (b) Run CRF
#======================================================================#
echo '(1b) DCD: Labeling'
$crftst -m $dcdm $fout.CONN.data > $fout.CONN.out

#======================================================================#
# (c) Post-Processing
#======================================================================#
echo '(1c) DCD: Post-Processing'
# extract connective spans
php $sdir/proc_conll.php \
	-f $fout.CONN.out \
	-c 0,1,2,3,4,7,19 \
	-u 0,1,2 > $fout.DCD.spans

# split data w.r.t. relations
php $sdir/pproc_spans_dcd.php \
	-f $fout.DCD.spans \
	-t $fout.CONN.out > $fout.DCD.rel

# extract relation spans
php $sdir/proc_conll.php \
	-f $fout.DCD.rel \
	-r $chtbl \
	-s ',' \
	-u 0,1,2 |\
	cut -d ',' -f 1-5,8-10,12,13,15-19 > $fout.ada.data

########################################################################
# (2) Connective Sense Classification
########################################################################
echo '============================================='
echo '(2) Connective Sense Classification (CSC)'
echo '============================================='
#======================================================================#
# (a) Make data file (CSV=icsi)
#======================================================================#
echo '(2a) CSC: Generating Data'
# add: type, tsense, fsense
cat $fout.ada.data | sed 's/$/,,./g' > $fout.CSC.data

#======================================================================#
# (b) Run classifier
#======================================================================#
echo '(2b) CSC: Labeling'
$adatst --names $cscn --model $cscm \
	-S $fout.CSC < $fout.CSC.data > $fout.CSC.out 2> $fout.CSC.log

########################################################################
# (3) Argument Position Classification
########################################################################
echo '============================================='
echo '(3) Argument Position Classification (APC)'
echo '============================================='
#======================================================================#
# (a) Make data file (CSV=icsi)
#======================================================================#
echo '(3a) APC: Generating Data'
# add: type,fsense,tsense,cfr
cat $fout.ada.data | sed 's/$/,,,./g' > $fout.APC.data

#======================================================================#
# (b) Run classifier
#======================================================================#
echo '(3b) APC: Labeling'
$adatst --names $apcn --model $apcm \
	-S $fout.APC < $fout.APC.data > $fout.APC.out 2> $fout.APC.log
#======================================================================#
# (c) Extract decisions & IDs
#======================================================================#
echo '(3c) APC: Extracting Decisions'
php $sdir/proc_adaboost.php \
	-n $apcn \
	-d $fout.APC.data \
	-o $fout.APC.out \
	-c 0,1,2 > $fout.APC.dec

#======================================================================#
# (d) Generate IDs
#======================================================================#
echo '(3d) APC: Generating IDs for ASE, NE-RSC & NE-ASE'

# extract SS & PS ids from $fout.APC.dec
cat $fout.APC.dec | grep 'SS' | cut -f 1-3 > $fout.SS.ids
cat $fout.APC.dec | grep 'PS' | cut -f 1-3 > $fout.PS.A2.ids

# generate PS.A1 IDs
php $sdir/gen_ids_ips.php -f $fout.PS.A2.ids > $fout.PS.A1.ids

# join PS IDs
paste $fout.PS.A1.ids $fout.PS.A2.ids |\
	cut -f 1-3,6 > $fout.sent.PS.pairs

# generate NE IDs
php $sdir/gen_ids_ne.php \
	-p $fout.sent.pairs \
	-f $fout.sent.PS.pairs > $fout.sent.NE.pairs

# generate NE Argument IDs
cat $fout.sent.NE.pairs | cut -f 1,2,3 > $fout.NE.A1.ids
cat $fout.sent.NE.pairs | cut -f 1,2,4 > $fout.NE.A2.ids

#======================================================================#
# Resource-based Feature Extraction/Generation (relation-level)
#  1. raw features
#  2. cartersian product of dependency roles/sentence-level features
#  3. matches of (2)
#======================================================================#
echo '---------------------------------------------'
echo 'Feature Extraction/Generation: relation-level'
echo '---------------------------------------------'
#----------------------------------------------------------------------#
# Dependency:
#----------------------------------------------------------------------#
echo 'Feature Generation: lemma pairs'
php $sdir/gen_feats_rel_dependency.php \
	-p $fout.sent.NE.pairs \
	-d $fout.sent.feats.dep.tsv \
	-l $fout.tok.feats.lemma.tsv \
	-t 2 > $fout.rel.feats.dep.tsv
#----------------------------------------------------------------------#
# Brown Cluster:
#----------------------------------------------------------------------#
echo 'Feature Generation: Brown Clusters pairs'
php $sdir/gen_feats_rel_dependency.php \
	-p $fout.sent.NE.pairs \
	-d $fout.sent.feats.dep.tsv \
	-l $fout.tok.feats.brown.tsv > $fout.rel.feats.brown.tsv
#----------------------------------------------------------------------#
# MPQA: IDs+{pol,pol,pol#pol,match}
#----------------------------------------------------------------------#
echo 'Feature Generation: MPQA polarity pairs'
php $sdir/gen_feats_rel_mpqa.php \
	-p $fout.sent.NE.pairs \
	-l $fout.sent.feats.mpqa.tsv \
	-t 3 > $fout.rel.feats.mpqa.tsv
#----------------------------------------------------------------------#
# VerbNet: IDs+{vn,vn,vn#vn,match}
#----------------------------------------------------------------------#
echo 'Feature Generation: VerbNet pairs'
php $sdir/gen_feats_rel_verbnet.php \
	-p $fout.sent.NE.pairs \
	-l $fout.sent.feats.vn.tsv > $fout.rel.feats.vn.tsv
echo
########################################################################
# (4) Argument Span Extraction
########################################################################
echo '============================================='
echo '(4) Argument Span Extraction (ASE)'
echo '============================================='
#======================================================================#
# (a) Make data file (CoNLL)
#======================================================================#
echo '(4a) ASE: Generating Data'
# SS.A2
php $sdir/gen_data_labels.php \
	-f $fout.DCD.rel \
	-s $fout.SS.ids > $fout.SS.A2.data

# PS.A1
php $sdir/gen_data_span.php \
	-f $fout.tok.feats.tsv \
	-s $fout.PS.A1.ids > $fout.PS.A1.data

# PS.A2
php $sdir/gen_data_labels.php \
	-f $fout.DCD.rel \
	-s $fout.PS.A2.ids > $fout.PS.A2.data

#======================================================================#
# (b) Run CRF
#======================================================================#
echo '(4b) ASE: Labeling: SS.A2'
$crftst -m $aseSSA2m $fout.SS.A2.data > $fout.SS.A2.out

echo '(4c) ASE: Labeling: SS.A1'
$crftst -m $aseSSA1m $fout.SS.A2.out > $fout.SS.A1.out

echo '(4d) ASE: Labeling: PS.A1'
$crftst -m $asePSA1m $fout.PS.A1.data > $fout.PS.A1.out

echo '(4e) ASE: Labeling: PS.A2'
$crftst -m $asePSA2m $fout.PS.A2.data > $fout.PS.A2.out

########################################################################
# (5) Non-Explicit Relation Sense Classification
########################################################################
echo '============================================='
echo '(5) NE Relation Sense Classification (NE-RSC)'
echo '============================================='
#======================================================================#
# (a) Make data file (CSV=icsi)
#======================================================================#
echo '(5a) NE-RSC: Generating Data'
# join relation-level features & remove IDs
#
#  1. mpqa       :  8 : 4 + 4
#  2. VerbNet    :  8 : 4 + 4
#  3. brown      : 32 : 4 + 4 + 4 + 10 + 10
#  4. dependency : 32 : 4 + 4 + 4 + 10 + 10

# @EXP: Modify w.r.t. experiments
paste $fout.rel.feats.mpqa.tsv \
	$fout.rel.feats.vn.tsv \
	$fout.rel.feats.brown.tsv \
	$fout.rel.feats.dep.tsv |\
	cut -f 1-8,13-16,21-48,53-80 > $fout.rel.feats.tsv

php $sdir/gen_data_rel.php \
	-f $fout.rel.feats.tsv \
	-r $chtbl > $fout.rel.tmp

#----------------------------------------------------------------------#
# Non-Explicit Relation Sense Classification (top senses):
#  1. $fout.RSC5.data
#  remove lines without a label
#----------------------------------------------------------------------#
# @EXP: Modify w.r.t. experiments
cat $fout.rel.tmp |\
	cut -d ',' -f 1-68,69,70 |\
	sed 's/$/./g' > $fout.RSC5.data

#----------------------------------------------------------------------#
# Non-Explicit Relation Sense Classification (full senses):
#  1. $fout.RSC.data
#  remove lines without a label
#----------------------------------------------------------------------#
cat $fout.rel.tmp |\
	sed 's/$/./g' > $fout.RSC.data

#======================================================================#
# (b) Run classifier
#======================================================================#
echo '(5b) NE-RSC: Labeling'
$adatst --names $rscn --model $rscm \
	-S $fout.RSC < $fout.RSC.data > $fout.RSC.out 2> $fout.RSC.log

########################################################################
# (6) Non-Explicit Argument Span Extraction
########################################################################
echo '============================================='
echo '(6) NE Argument Span Extraction (NE-ASE)'
echo '============================================='
#======================================================================#
# (a) Make data file (CoNLL)
#======================================================================#
echo '(6a) NE-ASE: Generating Data'

# NE.A1
php $sdir/gen_data_span.php \
	-f $fout.tok.feats.tsv \
	-s $fout.NE.A1.ids > $fout.NE.A1.data

# NE.A2
php $sdir/gen_data_span.php \
	-f $fout.tok.feats.tsv \
	-s $fout.NE.A2.ids > $fout.NE.A2.data

#======================================================================#
# (b) Run CRF
#======================================================================#
echo '(6b) NE-ASE: Labeling: NE.A1'
$crftst -m $aseNEA1m $fout.NE.A1.data > $fout.NE.A1.out

echo '(6c) NE-ASE: Labeling: NE.A2'
$crftst -m $aseNEA2m $fout.NE.A2.data > $fout.NE.A2.out

echo '%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%'
echo
########################################################################
# Post-Processing
########################################################################
echo '============================================='
echo 'Post-Processing Outputs'
echo '============================================='
#======================================================================#
# (a) Spans
#======================================================================#
rspans=('SS.A1' 'SS.A2' 'PS.A1' 'PS.A2' 'NE.A1' 'NE.A2')

echo '---------------------------------------------'
echo 'Extracting Spans'
echo '---------------------------------------------'
for s in ${rspans[@]}
do
	echo 'Extracting:' $s
	php $sdir/proc_conll.php \
		-f $fout.$s.out \
		-c 0,1,2,3,4,7 \
		-u 0,1,2 > $fout.$s.spans
done

php $sdir/proc_conll.php \
	-f $fout.DCD.rel \
	-c 0,1,2,3,4,7 \
	-u 0,1,2 > $fout.CONN.spans

#======================================================================#
# (b) Senses
#======================================================================#
echo '---------------------------------------------'
echo 'Extracting Senses'
echo '---------------------------------------------'

echo 'Extracting: CSC'
php $sdir/proc_adaboost.php \
	-n $cscn \
	-d $fout.CSC.data \
	-o $fout.CSC.out \
	-c 0,1,2 > $fout.CSC.senses

echo 'Extracting: NE-RSC'
php $sdir/proc_adaboost.php \
	-n $mdir/RSC.names \
	-d $fout.RSC.data \
	-o $fout.RSC.out \
	-c 0,1,2,3 > $fout.RSC.senses

########################################################################
# Extras
########################################################################
echo '============================================='
echo 'Running Extras'
echo '============================================='
#======================================================================#
# (a) Heuristics
#======================================================================#
echo '---------------------------------------------'
echo 'ASE Heuristics:'
echo '---------------------------------------------'

hs=('PS.A1' 'NE.A1' 'NE.A2')
for s in ${hs[@]}
do
	echo 'ASE Heuristic:' $s
	php $sdir/heuristic.php \
		-f $fout.CONN.data \
		-s $fout.$s.ids > $fout.$s.heur
done

########################################################################
# Generating json
########################################################################
echo '============================================='
echo 'Generating Ouput JSON'
echo '============================================='

php $sdir/out_json.php \
	-a $fout.SS.A1.spans \
	-b $fout.SS.A2.spans \
	-c $fout.PS.A1.spans \
	-d $fout.PS.A2.spans \
	-e $fout.NE.A1.spans \
	-f $fout.NE.A2.spans \
	-g $fout.CONN.spans \
	-r $fout.RSC.senses \
	-s $fout.CSC.senses \
	-l $ddir/punctuation.txt > $wdir/output.json

php $sdir/out_json.php \
	-a $fout.SS.A1.spans \
	-b $fout.SS.A2.spans \
	-c $fout.PS.A1.heur \
	-d $fout.PS.A2.spans \
	-e $fout.NE.A1.heur \
	-f $fout.NE.A2.heur \
	-g $fout.CONN.spans \
	-r $fout.RSC.senses \
	-s $fout.CSC.senses \
	-l $ddir/punctuation.txt > $wdir/houtput.json

########################################################################
echo '*********************************************'
echo 'DONE!'

cat $wdir/output.json > $odir/output.json
