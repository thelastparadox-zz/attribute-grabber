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

    }
    
    public function request_url($url)
    {
        // Check if page exists in cache and it's not too old
        $cached_page = DB::table('crawler_cache')->where('url', $url)->get()->first();

        if ($cached_page)
        {
            // Load page HTML from Cache
            $html = Storage::disk('local')->get($cached_page->raw_output);
            
            $return = new \stdClass();

            $return->body = $html;
            $return->cached = true;

            return $return;
        }
        else
        {
            $client = Client::getInstance();
        
            $client->getEngine()->setPath(base_path('node_modules/phantomjs-bin/bin/linux/x64/phantomjs'));
            //$client->getEngine()->debug(true);
            $client->isLazy();

            $request = $client->getMessageFactory()->createRequest();
            $response = $client->getMessageFactory()->createResponse();

            $request->addHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0');
            $request->setViewportSize(1600,1024);
            $request->setMethod('GET');
            $request->setUrl($url);
            $request->setTimeout(10000);

            $url_hash = md5($url);

            $client->send($request, $response);

            if($response->getStatus() === 200) 
            {
                // Save the output to cache
                $filename = 'crawler/'.$url_hash.".crawler.cache";

                if (Storage::disk('local')->put($filename, $response->getContent()))
                {
                    // Save result to cache
                    DB::table('crawler_cache')->insert([
                        'url' => $url,
                        'raw_output' => $filename,
                        'requested' => date("Y-m-d H:i:s"),
                    ]);
                }
                
                $return = new \stdClass();
                
                $return->body = $response->getContent();
                $return->cached = false;   

                return $return;
            }
            else
            {
                $return = new \stdClass();
                $return->body = 'Some kind of error occurred.';

                return $return;
            }
        }
    }

    public function extract_links($html, $pages_to_avoid, $root_site_url)
    {
        // Extract all links from page (https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*))
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');
        
        $saved_links = array();
        $deleted_links = array();

        // Strip URL to just include the domain and TLD for extracting purposes
        $domainAndTld = str_replace("/","",preg_replace(array("/\bhttp:\/\/\b/","/\bhttps:\/\/\b/"),"",$root_site_url));

        //echo '<h3>Pages to avoid:</h3><pre>'; print_r($pages_to_avoid); echo '</pre>';

        foreach ($links as $link)
        {                               
            // Check basic href structure
            //echo '<p>URL: '.$link->getAttribute('href').' | Domain ('.$domainAndTld.') found: '.strpos($link->getAttribute('href'), $domainAndTld).'</p>';

            // Check to see if any domain is found
            echo '<div><b>WORKING ON THIS LINK</b>: '.$link->getAttribute('href').'</div>';
            echo '<div>-----> Filtered: '.trim($link->getAttribute('href'), '!"#$%&\'()*+,-./@:;<=>[\\]^_`{|}~').'</div>';

            if (preg_match("/^((http[s]?|ftp):\/)?\/?([^:\/\s]+)((\/\w+)*\/)([\w\-\.]+[^#?\s]+)(.*)?(#[\w\-]+)?$/", $link->getAttribute('href'), $url_matches))
            {
                echo '<div>-----> Checking: URL contains a domain name.</div>';

                // if it does, check it is the site URL, if not, discard
            }
            else
            {
                echo '<div>-----> Checking: URL <b>DOES NOT</b> contain a domain name.</div>';

                // if the first letter is a forward slash, then form it into a nice URL
                if (substr($link->getAttribute('href'),0,1) == "/")
                {
                    echo '<div>-----> Checking: URL first letter is a forward slash.</div>';

                    // Clean up URL
                    if (substr($link->getAttribute('href'),0,2) == "//")
                    {
                        $cleanLink = "https:".$link->getAttribute('href');
                    }
                    else
                    {
                        $cleanLink = "https://".$domainAndTld.$link->getAttribute('href');
                    }
                }
                else
                {
                    array_push($deleted_links, $link->getAttribute('href'));
                }
            }

            // Check to see if any should be avoided
            if (count($pages_to_avoid) > 0)
            {
                foreach ($pages_to_avoid as $avoidedPage)
                {
                    if (!preg_match("/".$avoidedPage->url_pattern."/", $cleanLink))
                    {
                        array_push($saved_links, $cleanLink);                           
                    }
                    else
                    {
                        array_push($deleted_links, $cleanLink);
                    }
                }
            }
        }

        //echo '<h3>Saved links are as follows:</h3><pre>'; print_r($saved_links); echo '</pre>';
        //echo '<h3>Deleted links are as follows:</h3><pre>'; print_r($deleted_links); echo '</pre>';   
        
        return $saved_links;
    }

    public function process_url($url, $site_id)
    {
        // Get pages to avoid
        $pages_to_avoid = DB::table('sites_pages_avoid')->get()->where('site_id', $site_id);

        // Get Site Info
        $site = DB::table('sites')->get()->where('id', $site_id)->first();

        // Get URL
        $result = $this->request_url($url);

        // Extract Links
        $saved_links = $this->extract_links($result->body, $pages_to_avoid, $site->start_url);                 
        
        // Get Page Types
        $page_types = DB::table('sites_pages')->get()->where('site_id', $site_id);

        // Is the URL as per one of the patterns we are looking for?

        foreach ($page_types as $page_type)
        {
            if (preg_match("/".$page_type->url_pattern."/",$url))
            {
                // Page Found - extract data

                $extracted_items = $this->extract_items_from_html($result->body, $page_type->id, $url);                     
                
                if ($page_type->page_type == "category" && array_key_exists('breadcrumbs', $extracted_items))
                {
                    $breadcrumbs = json_decode($extracted_items['breadcrumbs']);
                    $category_name = $breadcrumbs[count($breadcrumbs)-1];

                    // check if category already exists
                    if (DB::table('categories')->where('category_name', $category_name)->get()->count() < 1)
                    {
                        // Save result to categories table
                        DB::table('categories')->insert(array_merge($extracted_items, array(
                            'category_name' => $category_name,
                            'site_id' => $site->id,
                            'url' => $url,
                            'created_at' => date("Y-m-d H:i:s")
                        )));
                    }          
                }
                if ($page_type->page_type == "product" && array_key_exists('breadcrumbs', $extracted_items))
                {
                    $breadcrumbs = json_decode($extracted_items['breadcrumbs']);
                    $category_name = $breadcrumbs[count($breadcrumbs)-1];

                    // check if product already exists
                    if (DB::table('categories')->where('category_name', $category_name)->get()->count() < 1)
                    {
                        // Save result to categories table
                        DB::table('categories')->insert(array_merge($extracted_items, array(
                            'category_name' => $category_name,
                            'site_id' => $site->id,
                            'url' => $url,
                            'created_at' => date("Y-m-d H:i:s")
                        )));
                    }
                }
            }
        }

        return $saved_links;
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

    public function extract_items_from_html($html, $page_type_id, $url)
    {
        // Get Items to extract from HTML
        $extract_items = DB::table('sites_pages_items')->get()->where('site_page_id', $page_type_id);

        //echo '<h2>Items to Extract</h2><pre>'; print_r($extract_items); echo '</pre>';

        $return_elements = array();
                
        foreach ($extract_items as $extract_item)
        {
            echo '<div><u>Working on finding <b>'.$extract_item->item_name.'</b> ('.$extract_item->identifier.')</u></div>';

            if ($extract_item->identifier_type == "css")
            {
                echo '<div>-----> Item Type: CSS Selector</div>';
                $converter = new CssSelectorConverter();
                
                $dom = new \DOMDocument;
                @$dom->loadHTML($html);
                
                $xpath = new \DOMXpath($dom);

                $xpath_value = $converter->toXPath($extract_item->identifier);

                $elements = $xpath->query($xpath_value);         

                
                echo '<div>-----> XPath Value: '.$xpath_value.'</div>';
                echo '<div>-----> # of elements found: '.$elements->length.'</div>';
                
                $collected_nodes = array();

                if (!is_null($elements) && $elements->length > 0) 
                {   
                    foreach ($elements as $element) 
                    {
                        //if ($extract_item->identifier == "#mainImage") { print_r($element->attributes); }
                    
                        //echo '<div>----------> Value Attribute: '.$element->getAttribute('value').'</div>';
                        //echo '<div>----------> # of Child Nodes: '.count($element->childNodes).'</div>';
                        
                        if ($extract_item->item_type == 'image')
                        {
                            array_push($collected_nodes, $element->getAttribute('src'));
                        }
                        elseif (strpos($extract_item->identifier,'input') !== false)
                        {
                            array_push($collected_nodes, $element->getAttribute('value'));
                        }
                        else
                        {
                            foreach ($element->childNodes as $node) 
                            {                                                              
                                // Clean result: remove spaces, newlines etc
                                //$cleanNode = preg_replace('/^[A-Za-z0-9_~\-!@#\$%\^&\*\(\)]+$/', '', trim(str_replace(array("\r", "\n", "\t", "\v", ","), "", $node->nodeValue)));
                                $cleanNode = trim(preg_replace('/[^A-Za-z0-9 !@#$%^&*().]/', '', $node->nodeValue));

                                //echo '<div>---------------> Node Value: '.$node->nodeValue.' && CleanNode: '.$cleanNode.'</div>';

                                // Check if it is an integer
                                if ($extract_item->item_type == 'integer' || $extract_item->item_type == 'price')
                                {
                                    // If it contains numbers and letters, strip the letters and see if it's still numeric
                                    $cleanNode = preg_replace("/[^0-9,.]/", "", $cleanNode);

                                    if (!is_numeric($cleanNode)) 
                                    {
                                        $cleanNode = "";
                                    }
                                }

                                // Remove
                                if (preg_match('/{{\s*[\w\.]+\s*}}/', $cleanNode))
                                {
                                    $cleanNode = "";
                                }                       

                                if ($cleanNode != "")
                                {                       
                                    array_push($collected_nodes, $cleanNode);
                                }
                            }
                        }
                    }
                }
                else
                {
                    //echo '<div>Its empty petal.</div>';
                }
            
                if ($extract_item->item_type == 'price' && count($collected_nodes) == 2)
                {
                    $collected_nodes = array($collected_nodes[0].".".$collected_nodes[1]);
                }

                if (count($collected_nodes) > 1)
                {
                    if ($extract_item->item_type == 'text')
                    {
                        $return_elements[$extract_item->db_column_name] = implode(" ", $collected_nodes);
                    }
                    else
                    {
                        $return_elements[$extract_item->db_column_name] = json_encode($collected_nodes);
                    }              
                }
                else
                {
                    if (count($collected_nodes) > 0)
                    {
                        $return_elements[$extract_item->db_column_name] = $collected_nodes[0];
                    }        
                }

            }
            
            if ($extract_item->identifier_type == "regex")
            {
                echo '<div>-----> Item Type: Regex</div>';
                if ($extract_item->item_type == 'url')
                {
                    if (preg_match($extract_item->identifier, $url, $matches))
                    {
                        $return_elements[$extract_item->db_column_name] = $matches;
                    }
                }
                else
                {
                    if (preg_match($extract_item->identifier, $html, $matches))
                    {
                        $return_elements[$extract_item->db_column_name] = $matches;
                    }
                }

            }
        }

        return $return_elements;
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