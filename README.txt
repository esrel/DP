UNITN Penn Discourse Treebank Discourse Parser

The Discourse Parser was developed as a University of Trento submission to 
CoNLL 2016 Shared Task on Multilingual Shallow Discourse Parsing and is an
extension of the parser developed for the CoNLL 2015 Shared Task on Shallow
Discourse Parsing.

A. Dependencies & Installation
The parser depends on the following 3rd party tools:

 - CRF++      : https://taku910.github.io/crfpp/
 - icsiboost  : https://github.com/benob/icsiboost
 - Tree Tagger: http://www.cis.uni-muenchen.de/~schmid/tools/TreeTagger/
 
After these tools are installed, the test.sh script might need to be edited
with paths to the executables and/or models.

B. Execution

The parser is run using test.sh script with 2 arguments: input and output 
directories.

The input directory must contain parses.json file (see section C) and raw 
textual files.

C. Input Data Format

Originally, the parser was designed to take the raw text and parses json file 
provided by the task organizers as an input. The json file contains sentence 
split and tokenized text, where each sentence is represented by:

 (1) dependency parse tree: list of triplets of dependency functions and dash 
    (-) separated head token sting and ID and token string and its ID. IDs
    are counted from 1.
 (2) constituency parse tree string
 (3) list of words, where each word is represented by:
    (a) token string
    (b) character-wise begin and end position of the token in the raw text
    (c) part-of-speech tag

Json file structure.

{"DocumentID": 
  {"sentences": 
    [
      {"dependencies": [
        ["function", "Head_token-Head_tokenID", "token-tokenID"], ...], 
       "parsetree": string, 
       "words": [
         [string, 
           {
             "CharacterOffsetBegin": int, 
             "CharacterOffsetEnd": int, 
             "PartOfSpeech": string
           }
         ], ...]
      }, ...
     ]
  }
}

Both json file and raw text file are required, and the folder with raw documents
should be in the same directory with the parses json file.

D. Output Data Format

The output of the parser is a json file in one json object per line format.
Each object represents a discourse relation in PDTB definitions, and consists
of 3 span definitions: connective, argument 1 and argument 2, sense and type of
a relation. The types and senses are from PDTB definitions.

{"DocID": "id_string", 
 "Arg1": {"TokenList": [0, 1, ...]}, 
 "Arg2": {"TokenList": [10, 11, ...]}, 
 "Connective": {"TokenList": []}, 
 "Sense": ["Expansion.Conjunction"], 
 "Type": "Implicit"}

E. Processing "Raw" Text Files

In order to parse raw text file we provide txt2json.sh script. As dependency 
and constituency parses are required, the additional dependencies are:

 - Berkeley Parser: https://github.com/slavpetrov/berkeleyparser
 - Stanford Parser: http://nlp.stanford.edu/software/lex-parser.shtml
 
The script will produce parser json file.
