<?php
namespace assignments\hw3;

use seekquarry\yioop\library\PhraseParser;
use seekquarry\yioop\locale\en_US\resources\Tokenizer;

function createTokenizedQueryArray($query,$tokenizationMethod)
{
    $tempQueryContents = explode(' ',strtolower($query));
    $tempQueryContents = preg_replace("#[[:punct:]]#", "", $tempQueryContents);
    $query = [];
    foreach($tempQueryContents as $queryWord) {
        if ( $tokenizationMethod == 'stem' ) {
            // Tokenize the given word by stemming it
            $query[] = implode('',PhraseParser::stemTerms($queryWord,"en-US"));
        }
        elseif ( $tokenizationMethod == 'chargram' ) {
            // Tokenize the given word by stemming it
            foreach( PhraseParser::getNGramsTerm([$queryWord],5) as $word ) {
                $query[] = $word;
            }
        }
        elseif ( $tokenizationMethod == 'none' ) {
            $query[] = $queryWord;
        }
    }
    return $query;
}
?>