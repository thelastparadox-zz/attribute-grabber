<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Remove when done testing
use Illuminate\Support\Facades\DB; // Remove when done testing
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use App\Library\Crawler;
use App\Library\Queue;
use Exception;

class CronController extends Controller
{
    public $pagestoAvoid = array();

    public $sitesInfo = array();

    public $pagesCache = array();

    public $timing = array('overall' => array(), 'request' => array());
    
    public function crawler()
    {
        return new Crawler();
    }

    public function cron_main(Request $request)
    {
        // Verify if the Worker has been authorised and has the token stored locally      
        if (!Storage::disk('local')->exists('config/autorisation.token'))
        {
            $auth = $this->crawler()->http_authorisation_request();
            
            if (property_exists($auth, 'auth_result'))
            {
                if ($auth->auth_result == 'fail')
                {
                    echo 'No authentication yet.';
                }
                else
                {
                    Storage::disk('local')->put('config/autorisation.token', $auth->token);
                    echo 'Authorised & token stored.';
                }
            }
            else
            {
                echo 'Some kind of issue with the authentication interface occurred.';
            }
        }
        else
        {
            $status = $this->crawler()->http_status_check();

            if (property_exists($status, 'error_code'))
            {
                if ($status->error_code == "INVALID_AUTH_CODE")
                {
                    Storage::disk('local')->delete('config/autorisation.token');
                }
                else
                {
                    echo $status->error_code.": ".$status->error_message;
                }
            }
            else
            {
                if ($status->crawl_enabled == true)
                {
                    $this->work_crawl_queue($request);
                }
                else
                {
                    echo 'Hang tight, crawl is disabled for this worker.'."\r\n";
                }
            }  
        }
    }

    private function work_crawl_queue(Request $request)
    {
        $total_processed = 0;
        $total_crawled = 0;

        $queue = new Queue();
        $queue->seed_queue('crawler-queue');

        DB::table('crawler_history')->truncate();

        for ($i = 1; $i <= 10; $i++)
        {
            // Start Timer
            array_push($this->timing['overall'], array('start' => date("Y-m-d H:i:s")));

            try 
            {
                $message = $queue->fetch_from_queue('crawler-queue');

                if ($message)
                {
                    $page = json_decode($message->content);
                    $total_processed++;
                    $crawler = new Crawler();

                    // Check history
                    $history = $crawler->http_history_check($page->url, $page->site_id);

                    if ($history)
                    {
                        if (!$history->exists)
                        {
                            // Check to see if the site information exists in cache
                            if (is_a( $site_info = $crawler->retrieve_site_info($page->site_id), 'Exception'))
                            {
                                throw new Exception($site_info);                             
                            }
                            
                            // Start Request Timer
                            array_push($this->timing['request'], array('start' => date("Y-m-d H:i:s")));

                            // Get URL
                            if (is_a( $result = $crawler->request_url($page->url), 'Exception'))
                            {
                                throw new Exception($result);
                            }

                            array_push($this->timing['request'], array('end' => date("Y-m-d H:i:s")));

                            // Check for pages to avoid
                            if (is_a( $pages_to_avoid = $crawler->retrieve_pages_avoid($page->site_id), 'Exception'))
                            {
                                throw new Exception($pages_to_avoid);                             
                            }

                            $saved_links = $crawler->extract_links($result->body, $pages_to_avoid, $site_info->start_url);
                            
                            // Check to see if the pages information exists in cache
                            if (is_a( $pages_info = $crawler->retrieve_pages($page->site_id), 'Exception'))
                            {
                                throw new Exception($pages_info);                             
                            }
            
                            // Is the URL as per one of the patterns we are looking for?         
                            foreach ($pages_info as $page_type)
                            {
                                //echo '<div>Analysing page type: '.$page_type->page_name.'</div>';
                                
                                if (preg_match("/".$page_type->url_pattern."/",$page->url))
                                {
                                    $page_items = $crawler->retrieve_page_items($page->site_id, $page_type->id);

                                    // Page Found - extract data
                                    $extracted_items = $crawler->extract_items_from_html($result->body, $page_type->id, $page->url, $page_items);                     

                                    if ($page_type->page_type == "category" && array_key_exists('breadcrumbs', $extracted_items))
                                    {
                                        $breadcrumbs = json_decode($extracted_items['breadcrumbs']);
                                        $category_name = $breadcrumbs[count($breadcrumbs)-1];
                
                                        // Save category info
                                        $extracted_items['category_name'] = $category_name;
                                        $extracted_items['site_id'] = $page->site_id;
                                        $extracted_items['url'] = $page->url;

                                        if (is_a($crawler->http_save_category($extracted_items), 'Exception'))
                                        {
                                            throw new Exception($extract_items);
                                        } 

                                    }
                                    if ($page_type->page_type == "product" && array_key_exists('breadcrumbs', $extracted_items))
                                    {
                                        $breadcrumbs = json_decode($extracted_items['breadcrumbs']);
                                        $category_name = $breadcrumbs[count($breadcrumbs)-1];
            
                                        // Save result to categories table
                                    }
                                }
                            } 
                        }
                        $total_crawled++;
                    }
                    else
                    {
                        throw new Exception($history);
                    }
                }
                else
                {
                    break;
                }

                $this->timing['overall']['end'] = date("Y-m-d H:i:s");
                $this->timing['overall']['total'] = strtotime($this->timing['overall']['end'])-strtotime($this->timing['overall']['start']);

            } catch (Exception $e) {
                //echo "<div>Error for URL: ".$page->url." - sent to error queue.</div>"; 
                throw new Exception ($e);
                // Push message on error queue with error  
                $queue->push_to_queue('error-queue', array(
                    'original_message' => $message->content,
                    'error' => $e
                ));
            }    
        }
        
        echo '<div>Total Messages collected: '.$total_processed.'</div>';
        echo '<div>Total Messages crawled: '.$total_crawled.'</div>';
        echo '<div>Total Messages skipped: '.($total_processed-$total_crawled).'</div>';
        echo '<h2>Timing</h2>';
        echo '<pre>'; print_r($this->timing); echo '</pre>';
        echo '<h2>Pages to Avoid</h2>';
        echo '<pre>'; print_r($this->pagestoAvoid); echo '</pre>';
        echo '<h2>Site Info</h2>';
        echo '<pre>'; print_r($this->sitesInfo); echo '</pre>';
        echo '<h2>Site Pages</h2>';
        echo '<pre>'; print_r($this->pagesCache); echo '</pre>';
        echo '<h2>Site Pages Items</h2>';
        echo '<pre>'; print_r($this->pageItemsCache); echo '</pre>';
    }
}