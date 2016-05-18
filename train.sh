#!/bin/bash
#
# CoNLL 2016 Shared Task: Shallow Discourse Parsing
#
# Training Pipeline (run once for train & dev -- evaluation references)
#
#======================================================================#
# Script Arguments: Input & Ouput directories
#======================================================================#

idir=$1 # Input  Directory (with data in JSON format)
odir=$2 # Output Directory [optional]

# set as wdir if not provided
if [[ $odir == '' ]]
then
	odir='wdir'
fi

wdir=$odir     # working directory
fout=$wdir/hyp # output file prefix

mkdir -p $wdir # Create Output Directory

########################################################################
# System Configuration
########################################################################
#======================================================================#
# Data Directories: Corpora, etc.
#======================================================================#
dat=$idir/relations.json # relations json (annotations)
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
adatrn='icsiboost -N ngram -W 1 -n 1000'
#----------------------------------------------------------------------#
# CRF++
#----------------------------------------------------------------------#
crftrn='crf_learn'

#======================================================================#
# Models
#======================================================================#
# file names for CRF data/template/model
spans=('CONN' 'SS.A1' 'SS.A2' 'PS.A1' 'PS.A2' 'NE.A1' 'NE.A2')
tasks=('APC' 'CSC' 'RSC')

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
# Process relations.json & output: IDs4={docID,relID,sentID,tokID}
#======================================================================#
#----------------------------------------------------------------------#
# Connective & Argument Spans & Labels
#  1. $fout.CONN.lbl    IDs4+{conn}                              (CoNLL)
#  2. $fout.CONN.ids    IDs3+{relID,token,conn}                  (CoNLL)
#  3. $fout.SS.A1.lbl   IDs4+{conn,arg2,arg1}                    (CoNLL)
#  4. $fout.SS.A2.lbl   IDs4+{conn,arg2}                         (CoNLL)
#  5. $fout.PS.A1.lbl   IDs4+{conn,arg2,arg1}                    (CoNLL)
#  6. $fout.PS.A2.lbl   IDs4+{conn,arg2}                         (CoNLL)
#  7. $fout.NE.A1.lbl   IDs4+{arg1}                              (CoNLL)
#  8. $fout.NE.A2.lbl   IDs4+{arg2}                              (CoNLL)
#----------------------------------------------------------------------#
echo 'Processing annotations: spans'
php $sdir/proc_relations.php -f $dat -t $fout.tok -n $fout

#----------------------------------------------------------------------#
# Relation Types & Senses: $fout.sense :
#  a. IDs={docID,relID,sentID_conn,sentID_arg1,sentID_arg2,dtID_conn}
#  b. Type   = [Explicit|Implicit|AltLex|EntRel]
#  c. Senses
#  d. Argument Postion Configuration [SS|PS|FS|0]
#----------------------------------------------------------------------#
echo 'Processing annotations: senses'
php $sdir/proc_relations_senses.php -f $dat > $fout.sense

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

#======================================================================#
# Resource-based Feature Extraction/Generation (relation-level)
#  1. raw features
#  2. cartersian product of dependency roles/sentence-level features
#  3. matches of (2)
#======================================================================#
echo '---------------------------------------------'
echo 'Feature Extraction/Generation: relation-level'
echo '---------------------------------------------'
# make non-explicit sentence pairs: remove multiple sentence spans
cat $fout.sense |\
	grep -v 'Explicit' |\
	cut -f 1,2,4,5 |\
	grep -v ',' > $fout.sent.NE.pairs

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

########################################################################
# Training/Testing Data Generation (join features & labels)
########################################################################
echo '============================================='
echo 'Data Generation'
echo '============================================='
#======================================================================#
# Discourse Connective Detection & Argument Span Extraction Data (CRF++)
#======================================================================#
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

echo 'Data Generation: DCD & ASE & NE-ASE'
for a in ${spans[@]}
do
	php $sdir/gen_data_span.php \
		-f $fout.tok.feats.tsv \
		-l $fout.$a.lbl > $fout.$a.data
done

#======================================================================#
# Connective Sense & Argument Position Classification data   (icsiboost)
#======================================================================#
echo 'Data Generation: APC & CSC'
php $sdir/gen_data_conn.php \
	-l $slist \
	-t $fout.tok.feats.tsv \
	-s $fout.sense \
	-r $chtbl \
	--sense 1 \
	--rm_partial |\
	cut -d ',' -f 1-5,8-10,12,13,15- > $fout.conn.tmp

#----------------------------------------------------------------------#
# Argument Position Classification
#----------------------------------------------------------------------#
# @EXP: Modify w.r.t. experiments
cat $fout.conn.tmp |\
	sed 's/$/./g' > $fout.APC.data

#----------------------------------------------------------------------#
# Connective Sense Classification (top senses)
#----------------------------------------------------------------------#
# @EXP: Modify w.r.t. experiments
# remove lines without a label
cat $fout.APC.data |\
	cut -d ',' -f 1-17 |\
	sed 's/$/./g' |\
	sed '/,\.$/d' > $fout.CSC4.data

#----------------------------------------------------------------------#
# Connective Sense Classification (full senses)
#----------------------------------------------------------------------#
# @EXP: Modify w.r.t. experiments
# remove lines without a label
cat $fout.APC.data |\
	cut -d ',' -f 1-18|\
	sed 's/$/./g' |\
	sed '/,\.$/d' > $fout.CSC.data

#======================================================================#
# Non-Explicit Relation Sense Classification data            (icsiboost)
#======================================================================#
echo 'Data Generation: NE-RSC'
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
	-l $slist \
	-s $fout.sense \
	-r $chtbl \
	--sense 1 \
	--rm_partial > $fout.rel.tmp

#----------------------------------------------------------------------#
# Non-Explicit Relation Sense Classification (top senses):
#  1. $fout.RSC5.data
#  remove lines without a label
#----------------------------------------------------------------------#
# @EXP: Modify w.r.t. experiments
cat $fout.rel.tmp |\
	cut -d ',' -f 1-68,69,70 |\
	sed 's/$/./g' |\
	sed '/,\.$/d' > $fout.RSC5.data

#----------------------------------------------------------------------#
# Non-Explicit Relation Sense Classification (full senses):
#  1. $fout.RSC.data
#  remove lines without a label
#----------------------------------------------------------------------#
cat $fout.rel.tmp |\
	sed 's/$/./g' |\
	sed '/,\.$/d' > $fout.RSC.data

########################################################################
# Training Models
########################################################################
echo '============================================='
echo 'Training Models'
echo '============================================='
#======================================================================#
# Train CRF Models
#======================================================================#
echo '---------------------------------------------'
echo 'Training Models: CRF'
echo '---------------------------------------------'
# default CRF training command & parameters
crfcmd="$crftrn -f 2" # use feature cut-off 2

for a in ${spans[@]}
do
	echo 'Training Models:' $a
	$crfcmd $tdir/$a.crf \
		$fout.$a.data \
		$mdir/$a.model > $fout.$a.log 2> $fout.$a.err
done
#======================================================================#
# Train icsiboost Models
#======================================================================#
# default icsiboost training command & parameters
echo '---------------------------------------------'
echo 'Training Models: icsiboost'
echo '---------------------------------------------'
for t in ${tasks[@]}
do
	echo 'Training Models:' $t
	$adatrn --names $mdir/$t.names --model $mdir/$t.shyp \
		-S $fout.$t > $fout.$t.log 2> $fout.$t.err
done

########################################################################
echo '*********************************************'

# remove temporary files
rm $fout.*.tmp

echo 'DONE!'
