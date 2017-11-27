<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
//use GuzzleHttp\Client;
use Symfony\Component\CssSelector\CssSelectorConverter;
use JonnyW\PhantomJs\Client;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class CrawlersController extends Controller
{
    public function crawlers_home(Request $request)
    {
        return view('crawlers');
    }

    public function process_crawler_queue(Request $request)
    {
        set_time_limit (900);

        $queue = DB::table('crawler_queue')->take(100)->get();

        foreach ($queue as $page)
        {    
            $saved_links = $this->process_url($site->url, $page->site_id);

            // Save Links to cache
            foreach ($saved_links as $saved_link)
            {
                // Check queue doesn't already contain link 
                $queued_page = DB::table('crawler_queue')->where('url', $saved_link)->get()->first();

                if ($queued_page == false)
                {
                    // Save result to queue
                    DB::table('crawler_queue')->insert([
                        'site_id' => $page->site_id,
                        'url' => $saved_link,
                        'added' => date("Y-m-d H:i:s"),
                    ]);
                }
            }

            // Delete item from Queue
            DB::table('crawler_queue')->where('id', $page->id)->delete();
        }

        // Check if Queue is empty and then set status of crawler to 'complete'
        $sites = DB::table('sites')->get();

        foreach ($site as $site)
        {
            $no_in_queue = DB::table('crawler_queue')->get()->where('site_id', $site->id)->count();
            
            if ($no_in_queue == 0)
            {
                DB::table('sites')->where('id', $site->id)->update(['status' => 'complete']);
            }
        }

    }

    function get_queue_stats()
    {
        $count = DB::table('crawler_queue')->get()->count();

        return $count;
    }

    function crawler_test_specific_page(Request $request)
    {
        // Test a specific page
        $page_array = array (
            0 => array(
                'url' => "https://www.homedepot.com/p/Sheetrock-UltraLight-1-2-in-x-4-ft-x-8-ft-Gypsum-Board-14113411708/202530243",
                'site_id' => 1,
            ),
            1 => array(
                'url' => "https://www.homedepot.com/p/Grip-Rite-6-x-1-1-4-in-Philips-Bugle-Head-Coarse-Thread-Sharp-Point-Drywall-Screws-1-lb-Pack-114CDWS1/100152392",
                'site_id' => 1,
            ),
            2 => array(
                'url' => "https://www.homedepot.com/p/Lumabase-10-Hour-Votive-Candle-72-Count-30872/205144654",
                'site_id' => 1,
            ),
            3 => array(
                'url' => "https://www.screwfix.com/p/grange-contempary-fence-panels-1-79-x-1-8m-3-pack/6908k",
                'site_id' => 2,
            ),  
            4 => array(
                'url' => "https://www.screwfix.com/c/outdoor-gardening/fence-panels/cat6530005",
                'site_id' => 2,
            ), 
            5 => array(
                'url' => "http://www.diy.com/departments/bathroom/toilets/close-coupled-toilets/DIY822172.cat?icamp=B%2FR_Toilets_C_CT&_requestid=179148",
                'site_id' => 3,
            ),                                               
        );

        $chosen = 5;

        // Get pages to avoid
        $pages_to_avoid = DB::table('sites_pages_avoid')->get()->where('site_id', $page_array[$chosen]['site_id']);
        // Get Site Info
        $site = DB::table('sites')->get()->where('id', $page_array[$chosen]['site_id'])->first();

        //echo '<h1>Processing this URL: '.$url.'</h1>'."\n";

        // Get URL
        $result = $this->request_url($page_array[$chosen]['url']);

        // Extract Links
        $saved_links = $this->extract_links($result->body, $pages_to_avoid, $site->start_url);
        
        //$saved_links = array();
        //echo '<div>Link count: '.count($saved_links).'</div>'."\n";                    
        
        // Get Page Types
        $page_types = DB::table('sites_pages')->get()->where('site_id', $page_array[$chosen]['site_id']);

        //print_r($page_types);

        // Is the URL as per one of the patterns we are looking for?
        $categories = array();
        $product_data = array();

        foreach ($page_types as $page_type)
        {
            if (preg_match("/".$page_type->url_pattern."/",$page_array[$chosen]['url']))
            {
                // Page Found - extract data

                $extracted_items = $this->extract_items_from_html($result->body, $page_type->id, $page_array[$chosen]['url']);  
                
                if (array_key_exists('breadcrumbs', $extracted_items))
                {
                    $breadcrumbs = json_decode($extracted_items['breadcrumbs']);
                    $category_name = $breadcrumbs[count($breadcrumbs)-1];
                    $extracted_items['category_name'] = $category_name;
                }          

            }
        }
       
        echo '<h1>Extracted Data</h1><pre>'; print_r($extracted_items); echo '</pre>';
        echo '<h1>Saved Links</h1><pre>'; print_r($saved_links); echo '</pre>';

        //echo $result->body; exit;
    }
}