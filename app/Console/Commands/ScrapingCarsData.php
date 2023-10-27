<?php

namespace App\Console\Commands;

use App\Models\Car;
use App\Models\Category;
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

    // get dom by url
    private function getDom($url) {
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
            ],
        ]);

        $contents = file_get_contents($url, false, $context);
        
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); // Ignore HTML errors
        $doc->loadHTML($contents);
        libxml_use_internal_errors(false);

         // Create a DOMXPath object to query the DOM
        return new DOMXPath($doc);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->getDom('https://www.list.am/category/23'); // get cars categories

        // $catDivContents = [];
        $divCatNodes = $path->query('//*[@id="ff"]/div[3]/div/div[2]/div[2]'); // get all categories

        foreach ($divCatNodes[0]->childNodes as $key => $divNode) {
            $catPath = $this->getDom( "https://www.list.am/category/23?n=&bid=" . $divNode->attributes['data-value']->textContent);
            $brandNodes = $catPath->query('//*[@id="ff"]/div[3]/div[2]/div[2]/div/div[2]'); // get all brands by selected category

            $catName = $divNode->textContent;
            $catId = $divNode->attributes['data-value']->textContent;

            $category = Category::firstOrCreate(
                ['list_code' => $catId],
                ['name' => $catName]
            );

            if ($brandNodes[0]) {
                foreach ($brandNodes[0]->childNodes as $value) {
                    $brandName = $value->textContent;
                    $brandId = $value->attributes['data-value']->textContent;
                    $brand = $category->brands()->firstOrCreate(
                        ['list_code' => $brandId],
                        ['name' => $brandName]
                    );

                    $carsXPath = '//*[@id="contentr"]/div[4]/div';
                    $brandPath = $this->getDom("https://www.list.am/category/23?n=&bid=" . $catId . "&mid=" . $brandId);
                    $carsPage = $brandPath->query($carsXPath); // get all cars by selected brand //

                    if (is_null($carsPage[0])) {
                        continue;
                    }
                    foreach ($carsPage[0]->childNodes as $val) {
                        $xPathName = 2;
                        $xPathPrice = 1;
                        $xPathImage = 0;
                        if (isset($val->childNodes[0]->childNodes[0]) && $val->childNodes[0]->childNodes[0]->textContent === 'Դիլեր') {
                            $xPathName += 1;
                            $xPathPrice += 1;
                            $xPathImage += 1;
                            if ($val->childNodes[3]->childNodes->textContent === 'Շտապ!') {
                                $xPathName += 1;
                            }
                        } else if (isset($val->childNodes[2]->childNodes[0]) && $val->childNodes[2]->childNodes[0]->textContent === 'Շտապ!') {
                            $xPathName += 1;
                        }
                        $carId = $val->attributes['href']->textContent;
                        $carName = $val->childNodes[$xPathName]->textContent;
                        $carPrice = $val->childNodes[$xPathPrice]->textContent;
                        $carImage = $val->childNodes[$xPathImage]->attributes['src']->textContent;
   
                        // get car list code
                        $pattern = "/\/item\/(\d+)/";
                        preg_match($pattern, $carId, $matches);
                        $listCode = $matches[1];

                        // get car year from full name
                        $pattern1 = '/\b\d{4}\b/';
                        if (preg_match($pattern1, $carName, $matches1)) {
                            $year = $matches1[0];
                        } else {
                            $year = 2020;
                        }

                        // get car amount and currency
                        $pattern2 = '/([\d,\.]+)\s*([^\d,\.]+)?/';
                        if (preg_match($pattern2, $carPrice, $matches2)) {
                            $amount = preg_replace('/[^0-9.]/', '', $matches2[1]);
                            $currency = strpos($carPrice, '$') !== false ? 'USD' : 'AMD';
                        } else {
                            $amount = 2222;
                            $currency = 'USD';
                        }
        
                        $car = Car::firstOrCreate(
                            ['list_code' => $listCode],
                            [
                                'year' => $year,
                                'name' => $carName,
                                'amount' => gettype($amount) === 'integer' ? $amount : 0,
                                'currency_code' => $currency,
                                'brand_id' => $brand->id,
                                'category_id' => $category->id
                            ]
                        );
                        $car->images()->firstOrCreate([
                            'image_path' => $carImage
                        ]);
                    }
                }
            }
            if($key > 1)break;
        }

        // https://www.list.am/category/23/2?bid=7&mid=1878 2-y paginationa
        ## es 4y misht 4 a te voch
    }
}
