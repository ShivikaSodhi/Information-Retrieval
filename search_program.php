<?php
namespace assignments\hw3;

use seekquarry\yioop\library\PhraseParser;
use seekquarry\yioop\locale\en_US\resources\Tokenizer;

require_once("create_inverted_index.php");
require_once("tokenize_query.php");
require_once("compute_cosine_rank.php");
require_once("compute_proximity_rank.php");
require_once("compute_bm25_rank.php");
require_once("compute_bm25f_rank.php");
require_once "vendor/autoload.php";
ini_set('error_reporting', E_ALL);
// This section parses the command line arguements and check their validity
$dirName = '';
$query = '';
$rankingMethod = '';
$tokenizationMethod = '';
$alpha = 0.0;
$purpose = "Description";
// The variable '$tokenizedQuery' holds the query array that has been tokenized based on the tokenization method provided in the command line
$tokenizedQuery;
// This variable '$invertedIndex' holds the inverted index that has been created after the tokenization method provided in the command line has been applied on the documents
$invertedIndex;
$totalNoDocs;
$descriptionScore = [];
$titleScore = [];
if (PHP_SAPI === 'cli') {
    if ($argc < 5 or $argc > 6) {
        echo ("\nInvalid numner of arguements passed to the program. Please see usage details below.\n");
        echo ("\nUsage : php search_program.php some_dir query ranking_method tokenization_method\n");
        exit();
    }
    $dirName = $argv[1];
    $query = $argv[2];
    $rankingMethod = $argv[3];
    $tokenizationMethod = $argv[4];
}
else {
    $dirName = ($_GET['argument1']);
    $query = ($_GET['argument2']);
    $rankingMethod = ($_GET['argument3']);
    $tokenizationMethod = ($_GET['argument4']);
}
if ( !( $rankingMethod == 'cosine' || $rankingMethod == 'proximity' || $rankingMethod == 'bm25' || $rankingMethod == 'bm25f' ) ) {
    echo ("The ranking method entered is not one of cosine, proximity, bm25, bm25f. Please enter a valid ranking method. Program will exit now.");
    exit();
}

if ( $rankingMethod == 'bm25f' ) {
    if ( $argc != 6 ) {
        echo("\nYou have chose the bm25f ranking method but have not provided a valid alpha. It is mandatory to provide alpha value for this ranking method. Program will exit now");
        exit();
    }
    else if ( $argv[5] < 0.0 || $argv[5] > 1.0 ) {
        echo("\nYou have specified an invalid value for alpha. Alpha's value should be set between 0.0 and 1.0\n");
        exit();
    }
    else {
        $alpha = $argv[5];
    }
}
if ( !( $tokenizationMethod == 'none' || $tokenizationMethod == 'stem' || $tokenizationMethod == 'chargram'  ) ) {
    echo ("The tokenization method entered is not one of none, stem, chargram. Please enter a valid tokenization method. Program will exit now.");
    exit();
}
echo  ("\n\nThis program strips all punctuation characters from the text and converts all text to lower case for the purpose of creating an inverted index.\n");
//Creating an inverted index for all the docs in the given directory
$adt = new Adt();
$adt->createInvertedIndex($dirName, $tokenizationMethod,"htm",$rankingMethod,$purpose);
$invertedIndex = $adt->getInvertedIndex();
$totalNoDocs = $adt->getTotalNoOfDocs();
$docLengths = $adt->getDocLengths();
$averageDocLength = array_sum($docLengths)/count($docLengths);
// Creating the tokenized query string
$tokenizedQuery = createTokenizedQueryArray($query,$tokenizationMethod);
/*
Call the coressponding scoring method based on the command line arguements
*/
if ( $rankingMethod == 'cosine' ) {
    echo ("\nThe Cosine Ranking method is called by this program.\n");
    cosineScore($invertedIndex,$totalNoDocs,$tokenizedQuery);
    echo ("\n\nThe program will be exiting now.\n");
    exit();
}
else if ($rankingMethod == 'proximity' ) {
    echo ("\nThe Proximity Ranking method is called by this program.\n");
    $proximityScore = new ProximityScore($invertedIndex,$totalNoDocs,$tokenizedQuery);
    $proximityScore->proximityScore();
    echo ("\n\nThe program will be exiting now.\n");
    exit();
}
else if (($rankingMethod == 'bm25' )) {
    echo ("\nThe BM25 Ranking method is called by this program.\n");
    $bm25 = new BM25Score($invertedIndex,$totalNoDocs,$tokenizedQuery,$docLengths,$averageDocLength);
    $bm25->rankBM25_DocumentAtATime_WithHeaps($rankingMethod,$purpose);
    echo ("\n\nThe program will be exiting now.\n");
    exit();
}
else if (($rankingMethod == 'bm25f' )) {
    echo ("\nThe BM25F Ranking method is called by this program.\n");
    $bm25f = new BM25Score($invertedIndex,$totalNoDocs,$tokenizedQuery,$docLengths,$averageDocLength);
    $descriptionScore = $bm25f->getDescriptionScores("bm25f",$purpose);
    /*
    Create inverted index for Document Titles and then call the BM25 score method 
    */
    $adt->createInvertedIndex($dirName, $tokenizationMethod,"htm",$rankingMethod,"Title");
    $invertedIndexOfTitle = $adt->getInvertedIndex();
    $bm25fTitle = new BM25Score($invertedIndexOfTitle,$totalNoDocs,$tokenizedQuery,$docLengths,$averageDocLength);
    $titleScore = $bm25f->getTitleScores("bm25f","Title");
    getBM25FScores($alpha,$totalNoDocs,$descriptionScore,$titleScore);
    echo ("\n\nThe program will be exiting now.\n");
    exit();
}
?>