<?php

use SilverStripe\Dev\BuildTask;
use GuzzleHttp\Client;
use SilverStripe\Core\Config\Configurable;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;
use DNADesign\Elemental\Models\ElementContent;

class GenerateRandomPagesTask extends BuildTask {
    use Configurable;
    public $title = 'Generate random pages from seed words';

    //flat text file containing our randomise set of words
    private static $word_list = "https://raw.githubusercontent.com/spesmilo/electrum/master/lib/wordlist/english.txt";

    //array of punctuation marks to periodically inject between words
    private static $punctuation_characters = ['.', '?', '!'];

    //words in Title
    private static $length_of_title = [
        'min' => 2,
        'max' => 10
    ];
    //words per paragraph
    private static $words_per_paragraph = 250;

    //max number of paragraphs per page
    private static $paragraphs_per_page = 5;

    //number of pages
    private static $num_pages = 25;

    public function run($request) {
        $cache = Injector::inst()->get(CacheInterface::class . '.ElliotSawyerRandomPagesCache');
        // retrieve the cache item
        if (!($this->words = $cache->get('wordslist'))) {
            $url = $this->config()->word_list;
            $client = new Client();
            $body = (string) $client->request('GET', $url)->getBody();
            if($body) {
                $this->words = explode("\n", $body);
                $cache->set('wordslist', $this->words);
            }
        }

        $this->numPages = $this->config()->num_pages;
        DB::alteration_message('Generating ' . $this->numPages . ' pages');
        while($this->numPages--) {
            shuffle($this->words);
            $this->makePage();
        }
    }

    private function makePage() {
        $titleLengths = $this->config()->length_of_title;
        $length = random_int($titleLengths['min'], $titleLengths['max']);
        $title = ucwords(
            implode(
                " ",
                array_slice($this->words, 0, $length)
            )
        );

        $contentElement = $this->makeContent();

        $page = Page::create();
        $page->Title = $title;
        $page->ElementalArea()->Elements()->push($contentElement);
        $page->ParentID = $this->getParentPage()->ID ?: 0;

        $page->write();
        $page->doPublish();
        DB::alteration_message(
            sprintf(
                "... %s",
                $title
            )
        );
    }

    private function makeContent() {
        $content = '';
        $this->paragraphs = $this->config()->paragraphs_per_page;
        $this->numWords = $this->config()->words_per_paragraph;


        $paragraph = array_slice($this->words, 0, $this->numWords);
        foreach($paragraph as $i => $word) {
            if (random_int(0, 20) % 7 == 0 && $i > 0) {
                $punctuation = $this->getRandomPunctuation();
                $content .= $punctuation . ' ' . ucfirst($word);
            } else {
                $content .= $word . ' ';
            }
        }
        $content .= '.';

        $contentElement = ElementContent::create();
        $contentElement->HTML = $content;
        $contentElement->write();
        $contentElement->doPublish();

        return $contentElement;
    }

    private function getRandomPunctuation() {
        $punctuation_characters = $this->config()->punctuation_characters;
        shuffle($punctuation_characters);
        return array_shift($punctuation_characters);
    }

    private function getParentPage() {

        //get pages (excluding Home and Error) that are in the toplevel
        $parentPage = Page::get()
            ->exclude('ClassName', ['HomePage', 'ErrorPage'])
            ->filter("ParentID", 0)
            ->first();
        if($parentPage && $parentPage->ID) {
            return $parentPage;
        }


    }
}
