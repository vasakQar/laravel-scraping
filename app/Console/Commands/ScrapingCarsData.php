<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Termwind\Components\Dd;

class ScrapingCarsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scraping-cars-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'scraping cars data from list.am';


    public function getDom($url){
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
            ],
        ]);

        $content = file_get_contents($url, false, $context);
        
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); // Ignore HTML errors
        $doc->loadHTML($content);
        libxml_use_internal_errors(false);

         // Create a DOMXPath object to query the DOM
        return new DOMXPath($doc);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $xpath = $this->getDom('https://www.list.am/category/23');
         // Extract div contents
        $divContents = [];
        $divNodes = $xpath->query('//*[@id="ff"]/div[3]/div/div[2]/div[2]');

        foreach ($divNodes[0]->childNodes as $key => $divNode) {
            $xpath2 = $this->getDom( "https://www.list.am/category/23?n=&bid=".$divNode->attributes['data-value']->textContent);
            $markNodes = $xpath2->query('//*[@id="ff"]/div[3]/div[2]/div[2]/div/div[2]');
            $model = [];
            if($markNodes[0]){
                foreach ($markNodes[0]->childNodes as $value) {
                    $model[] = [
                        'name' => $value->textContent,
                        'id' => $value->attributes['data-value']->textContent
                    ];
                }
            }
            $divContents[] = [
                'name' => $divNode->textContent,
                'id' => $divNode->attributes['data-value']->textContent,
                'model' => $model
            ];
            if($key > 1)break;
        }
        
        ### Example 
        $Make_id = $divContents[0]['id'];
        $model_id = $divContents[0]['model'][0]['id'];
        $dom = $this->getDom( "https://www.list.am/category/23?n=&bid=".$Make_id."&mid=".$model_id);
        // https://www.list.am/category/23/2?bid=7&mid=1878 2-y paginationa
        
        ## es 4y misht 4 a te voch
        $cars_page = $dom->query('//*[@id="contentr"]/div[4]/div');
        $cars = [];
        foreach ($cars_page[0]->childNodes as $key => $val) {
            $cars[] = [
                'id' => $val->attributes['href']->textContent,
                'image' => $val->childNodes[0]->attributes['src']->textContent,
                'price' => $val->childNodes[1]->textContent,
                'name' => $val->childNodes[2]->textContent
            ];
        }
        dd($cars);
    }
}
