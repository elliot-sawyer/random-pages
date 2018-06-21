<?php

use SilverStripe\Dev\BuildTask;


class GenerateRandomPagesTask extends BuildTask {
    public $title = 'Generate random pages from seed words';

    //flat text file containing our randomise set of words
    private static $word_list = "https://github.com/spesmilo/electrum/blob/master/lib/wordlist/english.txt";

    //When the random word divided by this number has a remainder of 0, insert a punctuation character
    private static $length_modulus = 7;

    //array of punctuation marks to periodically inject between words
    private static $punctuation_characters = [',', '.', '?', '!', ":", ";"];


    public function run($request) {
        // $client = new Client([
        //     // Base URI is used with relative requests
        //     'base_uri' => 'http://httpbin.org',
        //     // You can set any number of default request options.
        //     'timeout'  => 2.0,
        // ]);
    }

}
