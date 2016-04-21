<?php

namespace assignments\hw3;

use seekquarry\yioop\library\PhraseParser;

require_once("MinHeap.php");
require_once("MaxHeap.php");
require_once "vendor/autoload.php";

class BM25Score
{
    /*
    ** The invertedIndex variable will hold the inverted index with the appropriate tokenization applied.
    ** The queryArray variable will hold the query string tokenized on the appropriate tokenization method.
    */
    static $invertedIndex = [];
    static $queryArray = [];
    static $totalNoDocs = 0;
    static $cacheDocPos = [];
    static $k1 = 1.2;
    static $b = 0.75;
    static $averageDocLength;
    static $docLengths = [];
    static $descriptionResult = [];
    static $titleResult = [];
    function __construct($invertedIndex,$totalNoDocs,$tokenizedQuery,$docLengths,$averageDocLength) 
    {
        self::$queryArray = $tokenizedQuery;
        self::$invertedIndex = $invertedIndex;
        self::$totalNoDocs = $totalNoDocs;
        self::$docLengths = $docLengths;
        self::$averageDocLength = $averageDocLength;
    }
    function nextDoc($queryTerm, $currentDoc) 
    {
        $P = [];
        if ( array_key_exists( $queryTerm, self::$invertedIndex )) {
            $P = array_keys(self::$invertedIndex[$queryTerm]);
        }
        $l = count($P)-1;
        $low; 
        $high;
        $jump;
        $initialCachePos = 0;
        if ( $l == -1 || $P[$l] <= $currentDoc ) {
            return INF;
        }
        if ( $P[0] > $currentDoc ) {
            self::$cacheDocPos[$queryTerm] = 0;
            return $P[0];
        }
        if ( key_exists($queryTerm,self::$cacheDocPos ) ) {
            $initialCachePos = self::$cacheDocPos[$queryTerm];
        }
        if ( $initialCachePos > 0 && $P[$initialCachePos-1] <= $currentDoc ) {
            $low = $initialCachePos - 1;
        }
        else {
            $low = 0;
        }
        $jump = 1;
        $high = $low + $jump;
        while( $high < $l && $P[$high] <= $currentDoc ) {
            $low = $high;
            $jump = $jump * 2;
            $high = $low + $jump;
        }
        if ( $high > $l ) {
                $high = $l;
        }
        self::$cacheDocPos[$queryTerm] = $this->binarySearchNext( $P, $low, $high, $currentDoc  );
        return $P[self::$cacheDocPos[$queryTerm]];
    }
    function binarySearchNext($postingList, $low, $high, $current ) 
    {
        $mid = ceil(($high + $low)/2);
        if ($postingList[$mid - 1] <= $current && $postingList[$mid] > $current) {
            return $mid;
        } else if ($postingList[$mid] > $current) {
            return $this->binarySearchNext($postingList, $low, $mid-1, $current);
        } else if ($postingList[$mid] < $current) {
            return $this->binarySearchNext($postingList, $mid+1, $high, $current);
        } else {
            return $mid + 1;
        }
    }
    function getDescriptionScores($rankingMethod) 
    {
        $this->rankBM25_DocumentAtATime_WithHeaps($rankingMethod,"Description");
        return self::$descriptionResult;
    }
    function getTitleScores($rankingMethod) 
    {
        $this->rankBM25_DocumentAtATime_WithHeaps($rankingMethod,"Title");
        return self::$titleResult;
    }
    function printBM25Score($bm25Score)
    {
        $bm25Score->top();
        while ($bm25Score->valid()) {
            list ($doc, $docScore) = each ($bm25Score->current());
            echo ("(" . $doc . ', ' . rount($docScore,2) . ")\n");
            $bm25Score->next();
        }
    }
    function rankBM25_DocumentAtATime_WithHeaps($rankingMethod,$purpose)
    {
        $result = new MaxHeap();
        $term = new MinHeap();
        $TF = 0;
        $IDF = 0;
        foreach(self::$queryArray as $word) {
            $nextDocOfTerm = $this->nextDoc($word,-INF);
            $term->insert(array($word=>$nextDocOfTerm));
        }
        while(array_values($term->top())[0] < INF){
            $d = array_values($term->top())[0];
            $score = 0;
            if (is_null(array_values($term->top())[0]) or  array_values($term->top())[0] < 0) {
                break;
            }
            while(array_values($term->top())[0] == $d){
                $t = $term->extract(); 
                $word = array_keys($t)[0];
                $doc = array_values($t)[0];
                $IDF = log ( (self::$totalNoDocs/count(array_keys(self::$invertedIndex[$word]))), 2 ) ;
                if (  !array_key_exists($doc,self::$invertedIndex[$word])) {
                    $TF = 0;
                }
                else {
                    $freqTermDoc = (count(explode(',',self::$invertedIndex[$word][$doc].','  )) -1);
                    $TF = ($freqTermDoc * (self::$k1  + 1)) / ( $freqTermDoc + ( self::$k1 * ( (1 - self::$b) + ( (self::$b * self::$docLengths[$doc])/self::$averageDocLength ) ) ) );
                }
                $score += $IDF * $TF;
                $nd = $this->nextDoc($word,$doc);
                $term->insert(array($word => $nd));
                
            }
            $result->insert(array ($doc => $score));
            if ( $rankingMethod == "bm25f" && $purpose == "Description" ) {
                self::$descriptionResult[$doc] = $score;
            }
            else if ( $rankingMethod == "bm25f" && $purpose == "Title" ) {
                self::$titleResult[$doc] = $score;
            }   
        }
        if ($rankingMethod == "bm25" ) {
            $this->printBM25Score($result);
        }
    }
}
?>