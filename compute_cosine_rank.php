<?php
namespace assignments\hw3;

use seekquarry\yioop\library\PhraseParser;
use seekquarry\yioop\locale\en_US\resources\Tokenizer;

require_once "vendor/autoload.php";
ini_set('error_reporting', E_ALL);

function cosineScore($invertedIndex,$totalNoDocs,$queryArray) {
    $docIDF = [];
    $queryIDF = [];
    $len = 0;
    $unitLen = 0;
    $totalTerms = count(array_keys($invertedIndex));
    /* 
    Computing TF IDF Scores
    */
    for ($i = 0; $i < $totalNoDocs; $i++) {
        $docIDF[$i] = [];
        foreach ((array_keys($invertedIndex)) as $term) {
            $TF = 0;
            $IDF = log ( ($totalNoDocs/count(array_keys($invertedIndex[$term]))), 2 ) ;
            if (  !array_key_exists($i,$invertedIndex[$term])) {
                $TF = 0;
            }
            else {
                $TF = log ( (count(explode(',',$invertedIndex[$term][$i].',' )) -1), 2 ) +1  ;
            }
            $docIDF[$i][] = ( $IDF * $TF );
        }
    }
    /*
    Normalizing the document vectors
    */
    for ($i = 0; $i < $totalNoDocs; $i++) {
        $len = 0;
        $unitLen = 0;
        for ($j = 0; $j < $totalTerms; $j++  ) {
            $len += $docIDF[$i][$j] * $docIDF[$i][$j] ;
        }
        $unitLen = sqrt($len);
        for ($j = 0; $j < $totalTerms; $j++  ) {
            $docIDF[$i][$j] = $docIDF[$i][$j] / $unitLen ;
        }
    }
    /*
    Calculating Vector for Query Terms
    */
    $i = 0;
    foreach (  array_keys($invertedIndex) as $term) {
        $queryIDF[$i] = 0.0;
        if ( in_array($term, $queryArray) ) {
            $TF = log ( count(array_keys($queryArray, $term)) , 2) + 1 ;
            $IDF = log ( ($totalNoDocs/count(array_keys($invertedIndex[$term]))), 2 ) ;
            $queryIDF[$i] =  $TF * $IDF  ;
        }
        $i++;
    }
    /*
    Normalizing the query vector
    */
    $len = 0;
    $unitLen = 0;
    for ($i = 0; $i < $totalTerms; $i++  ) {
        $len += $queryIDF[$i] * $queryIDF[$i] ;
    }
    $unitLen = sqrt($len);
    for ($i = 0; $i < $totalTerms; $i++  ) {
        $queryIDF[$i] = $queryIDF[$i] / $unitLen ;
    }
    /*
    Computing dot product 
    */
    $score = [];
    for ($i=0; $i<$totalNoDocs; $i++) {
        $tempScore = 0;
        for ($j=0; $j<$totalTerms; $j++) {
            $tempScore +=  $docIDF[$i][$j] * $queryIDF[$j] ;
        }
        $score[$i] = $tempScore;
    }
    /*
    Printing the documents in order of their score
    */
    echo ("\nFinal order of Cosine Scores ranks.\n");
    arsort($score);
    $rankedDocs = array_keys($score);
    for ($i=0;$i<$totalNoDocs;$i++) {
        echo ("\n(" . $rankedDocs[$i] . "," . round($score[$rankedDocs[$i]],2) . ")");
    }
}
?>