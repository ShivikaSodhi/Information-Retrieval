<?php

namespace assignments\hw3;

require_once "vendor/autoload.php";

class MinHeap extends \SplMinHeap
{
    protected function Compare($array1, $array2)
    {
        $doc1 = array_values($array1);
        $doc2 = array_values($array2);
        
        if ($doc1[0] == $doc2[0]) {
            return 0;
        }
        return $doc1[0] > $doc2[0] ? -1 : 1;
    }   
}

?>