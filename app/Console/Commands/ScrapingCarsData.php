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
    private function getDom($url)
    {
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
            $catPath = $this->getDom("https://www.list.am/category/23?n=&bid=" . $divNode->attributes['data-value']->textContent);
            $brandNodes = $catPath->query('//*[@id="ff"]/div[3]/div[2]/div[2]/div/div[2]'); // get all brands by selected category

            $catName = $divNode->textContent;
            $catId = $divNode->attributes['data-value']->textContent;

            // for test bmw
            // if ($catId != 7) {
            //     continue;
            // }

            $category = Category::firstOrCreate(
                ['list_code' => $catId],
                ['name' => $catName]
            );

            if ($brandNodes[0]) {
                foreach ($brandNodes[0]->childNodes as $value) {
                    $brandName = $value->textContent;
                    $brandId = $value->attributes['data-value']->textContent;

                    // for test bmw seria 5
                    // if ($brandId != 110) {
                    //     continue;
                    // }

                    $brand = $category->brands()->firstOrCreate(
                        ['list_code' => $brandId],
                        ['name' => $brandName]
                    );
                    $brandPath = $this->getDom("https://www.list.am/category/23?n=&bid=$catId&mid=$brandId");

                    // $paginationPath = '//*[@id="contentr"]/div[4]/div[2]/span';
                    $paginationPath = '//*[@id="contentr"]/div[4]/div[2]//*[@class="pp"]';
                    $pagination = $brandPath->query($paginationPath);
                    $pageCount = isset($pagination[0]) ? $pagination[0]->childNodes[$pagination[0]->childNodes->length - 1]->textContent : 1;
                    
                    for ($i = 1; $i <= $pageCount; $i++) {
                        $brandPath = $this->getDom("https://www.list.am/category/23/$i?n=&bid=$catId&mid=$brandId");
                        
                        if ($i == $pageCount) {
                            $pagination = $brandPath->query($paginationPath);
                            $pageCount = isset($pagination[0]) ? $pagination[0]->childNodes[$pagination[0]->childNodes->length - 1]->textContent : 1;
                        }

                        dump($i, $pageCount);

                        $carsXPath = '//*[@id="contentr"]/div[4]/div';
                        $carsPage = $brandPath->query($carsXPath); // get all cars by selected brand //

                        if (is_null($carsPage[0])) {
                            continue;
                        }

                        foreach ($carsPage[0]->childNodes as $val) {
                            $xPathName = 2;
                            $xPathPrice = 1;
                            $xPathImage = 0;

                            if (isset($val->childNodes[0]->childNodes[0]) && $this->mbConvertEncoding($val->childNodes[0]->childNodes[0]->textContent) === 'Դիլեր') { // hnaravor e classov
                                $xPathName += 1;
                                $xPathPrice += 1;
                                $xPathImage += 1;
                                if (isset($val->childNodes[3]->childNodes[0]->attributes['class']) && $val->childNodes[3]->childNodes[0]->attributes['class']->value === 'lbls') {
                                    $xPathName += 1;
                                }
                            }

                            if ($val->childNodes[$xPathPrice]->attributes['class']->value != 'p') {
                                $xPathName -= 1;
                                $carPrice = 0;
                            } elseif ($this->mbConvertEncoding($val->childNodes[$xPathPrice]->childNodes[0]->textContent) === 'Փնտրում եմ') {
                                $carPrice = 0;
                            } else {
                                $carPrice = $this->mbConvertEncoding($val->childNodes[$xPathPrice]->textContent);
                            }
                            
                            if ($val->childNodes[$xPathName]->attributes['class']->value === 'lbls') {
                                $xPathName += 1;
                            }

                            $carId = $val->attributes['href']->textContent;
                            $carName = $this->mbConvertEncoding($val->childNodes[$xPathName]->textContent);
                            $carImage = $val->childNodes[$xPathImage]->attributes['src']->textContent;

                            // get car list code
                            $pattern = "/\/item\/(\d+)/";
                            preg_match($pattern, $carId, $matches);
                            $listCode = $matches[1];

                            // get car year from full name
                            $pattern1 = '/\b\d{4}\b/';
                            preg_match($pattern1, $carName, $matches1);
                            $year = isset($matches1[0]) ? $matches1[0] : 2022; // pntrum em
                            $year = +$year > 1988 && +$year < 2024 ? $year : 2022; // pntrum em

                            // get car amount and currency
                            $pattern2 = '/([\d,\.]+)\s*([^\d,\.]+)?/';
                            preg_match($pattern2, $carPrice, $matches2);
                            $amount = preg_replace('/[^0-9.]/', '', $matches2[1]);

                            if (strpos($carPrice, '$') !== false) {
                                $currency = 'USD';
                            } elseif (strpos($carPrice, '€') !== false) {
                                $currency = 'EUR';
                            } else {
                                $currency = 'AMD';
                            }

                            $car = Car::firstOrCreate(
                                ['list_code' => $listCode],
                                [
                                    'year' => $year,
                                    'name' => $carName,
                                    'amount' => gettype(+$amount) === 'integer' ? $amount : 0,
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
            }
        }
    }

    private function mbConvertEncoding($val)
    {
        return mb_convert_encoding($val, 'iso-8859-1', 'auto');
    }
}
