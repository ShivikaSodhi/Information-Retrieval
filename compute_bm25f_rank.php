<?php
namespace assignments\hw3;

use seekquarry\yioop\library\PhraseParser;

require_once("MaxHeap.php");
require_once "vendor/autoload.php";

function printBM25FScore($bm25fScore)
{
    $bm25fScore->top();
    while ($bm25fScore->valid()) {
        list ($doc, $docScore) = each ($bm25fScore->current());
        echo ("(" . $doc . ', ' . round($docScore,2) . ")\n");
        $bm25fScore->next();
    }
}
function getBM25FScores($alpha,$totalNoDocs,$descriptionScore,$titleScore)
{
    $titleScore;
    $descriptionScore;
    $bm25fScore = new MaxHeap();
    for ($docNo = 0; $docNo < $totalNoDocs; $docNo++) {
        $score = 0;
        if ( array_key_exists($docNo,$descriptionScore) ) {
            $score += (1 - $alpha) * $descriptionScore[$docNo];
        }
        
        if ( array_key_exists($docNo,$titleScore) ) {
            $score += $alpha * $titleScore[$docNo];
        }
        $bm25fScore->insert(array ($docNo => $score));
    }
    echo("\nPlease find below the BM25F Scores :\n");
    printBM25FScore($bm25fScore);
}
?>