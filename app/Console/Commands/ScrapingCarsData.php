<?php

namespace App\Console\Commands;

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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
            ],
        ]);

        $content = file_get_contents('https://www.list.am/category/23', false, $context);
        
        dd(gettype($content));
    }
}
