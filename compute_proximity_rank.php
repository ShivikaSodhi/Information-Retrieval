<?php

namespace assignments\hw3;

use seekquarry\yioop\library\PhraseParser;
use seekquarry\yioop\locale\en_US\resources\Tokenizer;

require_once "vendor/autoload.php";

class ProximityScore
{
    /*
    ** The invertedIndex variable will hold the inverted index with the appropriate tokenization applied.
    ** The queryArray variable will hold the query string tokenized on the appropriate tokenization method.
    ** The static variable uvArray will hold u,v values for computing nextCover.
    */
    public $invertedIndex = [];
    public $queryArray = [];
    public $totalNoDocs = 0;
    static $cacheDocPos = [];
    static $cacheTermPos = [];
    static $cachePos = [];
    static $uvArray = [0,0];
    function __construct($invertedIndex,$totalNoDocs,$tokenizedQuery) {
        $this->queryArray = $tokenizedQuery;
        $this->invertedIndex = $invertedIndex;
        $this->totalNoDocs = $totalNoDocs;
    }
    function nextDoc($queryTerm, $currentDoc) 
    {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex )) {
            $P = array_keys($this->invertedIndex[$queryTerm]);
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
    function prevDoc($queryTerm, $currentDoc) 
    {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex )) {
            $P = array_keys($this->invertedIndex[$queryTerm]);
        }
        $l = count($P)-1;
        $low; 
        $high;
        $jump;
        $initialCachePos = 0;
        if ( $l == -1 || $P[0] >= $currentDoc ) {
            return -INF;
        }
        if ( $P[$l] < $currentDoc ) {
            self::$cacheDocPos[$queryTerm] = $l;
            return $P[$l];
        }
        if ( key_exists($queryTerm,self::$cacheDocPos ) ) {
            $initialCachePos = self::$cacheDocPos[$queryTerm];
        }
        if ( $initialCachePos < $l && $P[$initialCachePos+1] >= $currentDoc ) {
            $high = $initialCachePos + 1;
        }
        else {
            $high = $l;
        }
        $jump = 1;
        $low = $high - $jump;
        while( $low > 0 && $P[$high] >= $currentDoc ) {
            $high = $low;
            $jump = $jump * 2;
            $low = $high - $jump;
        }
        if ( $low < 0 ) {
                $low = 0;
        }
        self::$cacheDocPos[$queryTerm] = $this->binarySearchPrev( $P, $low, $high, $currentDoc  );
        return $P[self::$cacheDocPos[$queryTerm]];
    }
    /*
    Returns the first document in the corpus in which the term appears
    */
    function firstDoc($queryTerm) {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex ) ) {
            $P = array_keys($this->invertedIndex[$queryTerm]);
        }
        if ( count($P) == 0) {
            return INF;
        }
        else {
            return $P[0];
        }
    }
    /*
    Returns the last document in the corpus in which the term appears
    */
    function lastDoc($queryTerm) {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex ) ) {
            $P = array_keys($this->invertedIndex[$queryTerm]);
        }
        if ( count($P) == 0) {
            return INF;
        }
        else {
            return $P[count($P)-1];
        }
    }
    function next($queryTerm, $currentPos, $docNo) 
    {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex ) ) {
            if ( array_key_exists( $docNo, $this->invertedIndex[$queryTerm] ) ) {
                $P = explode( ",",($this->invertedIndex[$queryTerm][$docNo]) );
            }
        }
        $l = count($P)-1;
        $low; 
        $high;
        $jump;
        $initialCachePos = 0;
        if ( $l == -1 || $P[$l] <= $currentPos ) {
            return INF;
        }
        if ( $P[0] > $currentPos ) {
            self::$cachePos[$queryTerm] = 0;
            return $P[0];
        }
        if ( key_exists($queryTerm,self::$cacheDocPos ) ) {
            $initialCachePos = self::$cachePos[$queryTerm];
        }
        if ( $initialCachePos > 0 && $P[$initialCachePos-1] <= $currentPos ) {
            $low = $initialCachePos - 1;
        }
        else {
            $low = 0;
        }
        $jump = 1;
        $high = $low + $jump;
        while( $high < $l && $P[$high] <= $currentPos ) {
            $low = $high;
            $jump = $jump * 2;
            $high = $low + $jump;
        }
        if ( $high > $l ) {
                $high = $l;
        }
        self::$cachePos[$queryTerm] = $this->binarySearchNext( $P, $low, $high, $currentPos  );
        return $P[self::$cachePos[$queryTerm]];
    }
    function prev($queryTerm, $currentPos, $docNo) 
    {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex ) ) {
            if ( array_key_exists( $docNo, $this->invertedIndex[$queryTerm] ) ) {
                $P = explode( ",",($this->invertedIndex[$queryTerm][$docNo]) );
            }
        }
        $l = count($P)-1;
        $low; 
        $high;
        $jump;
        $initialCachePos = 0;
        if ( $l == -1 || $P[0] >= $currentPos ) {
            return -INF;
        }
        if ( $P[$l] < $currentPos ) {
            self::$cacheDocPos[$queryTerm] = $l;
            return $P[$l];
        }
        if ( key_exists($queryTerm,self::$cacheDocPos ) ) {
            $initialCachePos = self::$cacheDocPos[$queryTerm];
        }
        if ( $initialCachePos < $l && $P[$initialCachePos+1] >= $currentPos ) {
            $high = $initialCachePos + 1;
        }
        else {
            $high = $l;
        }
        $jump = 1;
        $low = $high - $jump;
        while( $low > 0 && $P[$low] >= $currentPos ) {
            $high = $low;
            $jump = $jump * 2;
            $low = $high - $jump;
        }
        if ( $low < 0 ) {
                $low = 0;
        }
        self::$cachePos[$queryTerm] = $this->binarySearchPrev( $P, $low, $high, $currentPos  );
        return $P[self::$cachePos[$queryTerm]];
    }
    /*
    Returns the first occurance of the given term in a given doc
    */
    function first($queryTerm, $docNo) {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex ) ) {
            if ( array_key_exists( $docNo, $this->invertedIndex[$queryTerm] ) ) {
                $P = explode( ",",($this->invertedIndex[$queryTerm][$docNo]) );
            }
        }
        if ( count($P) <= 1) {
            return INF;
        }
        else {
            return $P[0];
        }
    }
    /*
    Returns the last occurance of the given term in a given doc
    */
    function last($queryTerm, $docNo) {
        $P = [];
        if ( array_key_exists( $queryTerm, $this->invertedIndex ) ) {
            if ( array_key_exists( $docNo, $this->invertedIndex[$queryTerm] ) ) {
                $P = explode( ",",($this->invertedIndex[$queryTerm][$docNo]) );
            }
            
        }
        if ( count($P) <= 1) {
            return INF;
        }
        else {
            return $P[count($P)-1];
        }
    }
    /*
    This binary search algo can be used for finding previous. For future write one single Binary Search algo that can work for both next and previous
    */
    function binarySearchPrev($postingList, $low, $high, $current ) {
        $mid = floor(($high + $low)/2);
        if ($postingList[$mid + 1] >= $current && $postingList[$mid] < $current) {
            return $mid;
        } else if ($postingList[$mid] > $current) {
            return $this->binarySearchPrev($postingList, $low, $mid-1, $current);
        } else if ($postingList[$mid] < $current) {
            return $this->binarySearchPrev($postingList, $mid+1, $high, $current);
        } else {
            return $mid - 1;
        }
    }
    /*
    This binary search algo can be used for finding next. For future write one single Binary Search algo that can work for both next and previous
    */
    function binarySearchNext($postingList, $low, $high, $current ) {
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
    function nextCover($queryTerm, $position, $docNo) {
        $maxPos = -1;
        $minPos = INF;
        $v;
        $u;
        foreach ($queryTerm as $term) {
            $nextForThisTerm = $this->next($term, $position, $docNo);
            if ( $nextForThisTerm > $maxPos ) {
                $maxPos = $nextForThisTerm;
            }
        }
        $v = $maxPos;
        if ($v == INF) {
            self::$uvArray = [INF,INF]; 
        }
        else {
            foreach ($queryTerm as $term) {
            $nextForThisTerm = $this->prev($term, $v+1, $docNo);
            if ( $nextForThisTerm < $minPos ) {
                $minPos = $nextForThisTerm;
                }
            }
            $u = $minPos;
            self::$uvArray = [$u,$v]; 
        }
    }
    function findCommonDocuments() {
        $queryArray = $this->queryArray;
        $commonDocs = [];
        if ( array_key_exists($queryArray[0], $this->invertedIndex) ) {
            $commonDocs = array_keys($this->invertedIndex[$queryArray[0]]);
        }
        if ( count($commonDocs) < 1 ) {
            return ([]);
        }
        else {
            $remainingQueries = [];
            for ($i = 1; $i < count($queryArray); $i++) {
                $remainingQueries += [$queryArray[$i]];
            }
            if ( count($remainingQueries) > 0 ) {
                foreach ($remainingQueries as $term) {
                    $temp = [];
                    $temp =  array_intersect( $commonDocs,array_keys($this->invertedIndex[$term]) ) ;
                    $commonDocs = $temp;
                }
            }
            return $commonDocs;
        }
    }
    function printProximityScores($proximityScore) {
        $rankedDocs = array_keys($proximityScore);
        for ($i=0;$i<$this->totalNoDocs;$i++) {
            echo ("\n(" . $rankedDocs[$i] . ",". round($proximityScore[$rankedDocs[$i]],2) . ")");
        }
    }
    function proximityScore() {
        
        $commonDocIds = $this->findCommonDocuments();
        $proximityScore = [];
        /*
        If there are no documents that contain all the query terms then return a score of 0.0 for all documents
        */
        if (count($commonDocIds) < 1) {
            for ($i=0;$i<$this->totalNoDocs;$i++) {
                $proximityScore[$i] = 0.0;
            }
            echo ("\nFinal ranking of Proximity Scores: \n");
            $this->printProximityScores($proximityScore);
            return ;
        }
        else {
            /*
            Computes the score by taking in to consideration all covers for all documents which contain all the query terms
            */
            foreach ($commonDocIds as $commonDoc) {
                $proximityScore[$commonDoc] = 0;
                // Since our indices start from 0 in the document we have to set u to -1 to get the correct results
                self::$uvArray = [-1,-1];
                while (1) {
                    
                    $this->nextCover($this->queryArray, self::$uvArray[0], $commonDoc);
                    if (self::$uvArray[0] >= INF) {
                        break;
                    }
                    $proximityScore[$commonDoc] += 1 / ( self::$uvArray[1] - self::$uvArray[0] + 1);
                }
            }
            /*
            Printing the documents in order of the score they obtained
            This assigns a score of zero to all the documents which did not have all the query terms
            */
            for ($i=0;$i<$this->totalNoDocs;$i++) {
                if (! array_key_exists($i,$proximityScore) ) {
                    $proximityScore[$i] = 0.0;
                }
            }
            echo ("\nFinal ranking of Proximity Scores: \n");
            arsort($proximityScore);
            $this->printProximityScores($proximityScore);
            return; 
        }
    }

}
?>