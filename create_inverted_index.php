<?php
namespace assignments\hw3;

use seekquarry\yioop\configs\Config;
use seekquarry\yioop\library\PhraseParser;
use seekquarry\yioop\library\CrawlConstants;
use seekquarry\yioop\library\processors\HtmlProcessor;

require_once "vendor/autoload.php";
ini_set('error_reporting', E_ALL);
class Adt implements CrawlConstants 
{
    /*
    ** The invertedIndex variable will hold the inverted index with the appropriate tokenization applied.
    ** The queryArray variable will hold the query string tokenized on the appropriate tokenization method.
    ** The static variable uvArray will hold u,v values for computing nextCover.
    */
    public $invertedIndex = [];
    public $queryArray = [];
    public $totalNoDocs = 0;
    public $docLengths = [];
    static $cacheDocPos = [];
    static $cacheTermPos = [];
    static $cachePos = [];
    static $uvArray = [0,0];
    function getTotalNoOfDocs() 
    {
        return $this->totalNoDocs;
    }
    function getInvertedIndex() 
    {
        return $this->invertedIndex;
    }
    function getDocLengths()
    {
        return $this->docLengths;
    }
    function globFiles($dirName,$typeOfFiles) 
    {
        $listOfFiles = glob(rtrim($dirName)."/*.".$typeOfFiles."*");
        natsort($listOfFiles);
        return($listOfFiles);
    }
    function processHTMLFile($dirName,$fileContentsWithPunctuations,$rankingMethod,$purpose) 
    {
        if (!defined("seekquarry\\yioop\\configs\\PROFILE")) {
            define("seekquarry\\yioop\\configs\\PROFILE", true);
        }
        if (!defined("seekquarry\\yioop\\configs\\NO_LOGGING")) {
            define("seekquarry\\yioop\\configs\\NO_LOGGING", true);
        }
        $htmlProcessor = new HtmlProcessor([],20000,self::CENTROID_SUMMARIZER);
        $summary = $htmlProcessor->process($fileContentsWithPunctuations, 'http://www.d3rw.com');
        $title = trim(strtolower(preg_replace("#[[:punct:]]#", " ", $summary[self::TITLE])));
        $description = trim(strtolower(preg_replace("#[[:punct:]]#", " ", $summary[self::DESCRIPTION])));
        if ($rankingMethod == "bm25f")
            $description = substr($description,strlen($title));
        if ($purpose == "Description") {
            return $description;
        }
        elseif ($purpose == "Title") {
            return $title;
        }
    }
    function createInvertedIndex($dirName, $tokenizationMethod, $typeOfFiles, $rankingMethod, $purpose)
    {
        $document_id = 0;
        $termFrequency = [];
        $termDocMapping = [];
        $termDocPositionMapping = [];
        $files = $this->globFiles($dirName,$typeOfFiles);
        if ( count($files) < 1) {
            echo ("There are no files in the given directory.\n\nProgram will exit now.\n" );
            exit();
        }
        $this->totalNoDocs = count($files);
        foreach( $files as $fname ) {
            $fileContentsWithPunctuations = file_get_contents($fname);
            if ( $rankingMethod == "bm25f" && $purpose == "Description" ) {
                $fileContents = $this->processHTMLFile($dirName,$fileContentsWithPunctuations,$rankingMethod,$purpose);
            }
            else if ( $rankingMethod == "bm25f" && $purpose == "Title" ) {
                $fileContents = $this->processHTMLFile($dirName,$fileContentsWithPunctuations,$rankingMethod,$purpose);
            }
            else if ($rankingMethod != "bm25f") {
                $fileContents = $this->processHTMLFile($dirName,$fileContentsWithPunctuations,$rankingMethod,$purpose);
            }
            $fileContents = explode(" ", $fileContents);
            $position = 0;
            $termDocPositions = [];
            foreach ($fileContents as $word) {
                if (trim($word) == "") {
                    continue;
                }
                if ( $tokenizationMethod == 'none' ) {
                    // Get the term frequency
                    if ( !array_key_exists( $word,$termFrequency ) ) {
                        $termFrequency[$word] = 1;
                    }
                    else {
                        $termFrequency[$word] += 1;
                    }
                    // Get the term mapped with the document id, this will be used for finding the no of docs the term is associated with
                    if ( !array_key_exists( $word,$termDocMapping ) ) {
                        $termDocMapping[$word] = [$document_id]  ;
                    }
                    else {
                        $termDocMapping[$word][]  = $document_id;
                    }
                    // Get all positions where the word occurs in the document
                    if ( !array_key_exists( $word,$termDocPositions ) ) {
                        $termDocPositions[$word] = (string)$position ;
                    }
                    else {
                        $termDocPositions[$word]  .= "," . (string)$position ;
                    }            
                    $position += 1;
                }
                elseif ( $tokenizationMethod == 'stem' ) {
                    // Tokenize the given word by stemming it
                    $word = implode("",PhraseParser::stemTerms($word,"en-US"));
                    if ( !array_key_exists( $word,$termFrequency ) ) {
                        $termFrequency[$word] = 1;
                    }
                    else {
                        $termFrequency[$word] += 1;
                    }
                    // Get the term mapped with the document id, this will be used for finding the no of docs the term is associated with
                    if ( !array_key_exists( $word,$termDocMapping ) ) {
                        $termDocMapping[$word] = [$document_id]  ;
                    }
                    else {
                        $termDocMapping[$word][]  = $document_id;
                    }
                    // Get all positions where the word occurs in the document
                    if ( !array_key_exists( $word,$termDocPositions ) ) {
                        $termDocPositions[$word] = (string)$position ;
                    }
                    else {
                        $termDocPositions[$word]  .= "," . (string)$position ;
                    }            
                    $position += 1;
                }
                elseif ( $tokenizationMethod == 'chargram' ) {
                    // Tokenize the given word by stemming it
                    $charGrammedWords = PhraseParser::getNGramsTerm([$word],5);
                    foreach ( $charGrammedWords as $word ) {
                        if ( !array_key_exists( $word,$termFrequency ) ) {
                            $termFrequency[$word] = 1;
                        }
                        else {
                            $termFrequency[$word] += 1;
                        }
                        // Get the term mapped with the document id, this will be used for finding the no of docs the term is associated with
                        if ( !array_key_exists( $word,$termDocMapping ) ) {
                            $termDocMapping[$word] = [$document_id]  ;
                        }
                        else {
                            $termDocMapping[$word][]  = $document_id;
                        }
                        // Get all positions where the word occurs in the document
                        if ( !array_key_exists( $word,$termDocPositions ) ) {
                            $termDocPositions[$word] = (string)$position ;
                        }
                        else {
                            $termDocPositions[$word]  .= "," . (string)$position ;
                        }            
                    }
                    $position += 1;
                }
                $this->docLengths[$document_id] = $position;
            }
            foreach( $termDocPositions as $terms => $positions ) {
                if ( !array_key_exists( $terms,$termDocPositionMapping ) ) {
                    $termDocPositionMapping[$terms] = [$document_id => $termDocPositions[$terms]];
                }
                else {
                    $termDocPositionMapping[$terms] += [$document_id => $termDocPositions[$terms]];
                }
            }
            $document_id += 1;
        }
        // Sorting the array based on the terms for printing the inverted index
        ksort($termDocPositionMapping);
        $this->invertedIndex = $termDocPositionMapping;
    }
}