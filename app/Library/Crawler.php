<?php

namespace App\Library;

use Symfony\Component\CssSelector\CssSelectorConverter;
use JonnyW\PhantomJs\Client as PhantomJSClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client; 
use Exception;
use Carbon\Carbon;

class Crawler
{
    public function get_token()
    {
        return Storage::disk('local')->get('config/autorisation.token');
    }
    
    public function api_host()
    {
        return env ("ATTRIBUTE_API_HOST");
    }

    public function http_request($url, $type, $data=false, $includeToken=true)
    {
        try {
            if ($includeToken == true)
            {   
                $url = $url."?token=".$this->get_token();
            }
    
            $return = new \stdClass();

            $client = new Client(['http_errors' => false]);
            
            if ($type == "GET")
            {
                $res = $client->request('GET', $url);
            }
            else
            {
                $res = $client->request('POST', $url, ['json' => $data]);
            }

            if (!$res)
            {
                throw new Exception("Could not get request for URL ['.$url.']");
            }
            else            
            {
                if ($res->getStatusCode() == '200')
                {
                    $response = json_decode($res->getBody());

                    return $response;         
                }
                else
                {
                    throw new Exception("HTTP ERROR CODE ".$res->getStatusCode()." for URL [".$url."]");
                }
            }
        } catch (RequestException $e) 
        {
            return $e;
        }
    }
    
    public function http_history_check($url, $site_id)
    {   
        return $response = $this->http_request($this->api_host().'/api/crawler/history/check', 'POST', array('url' => $url, 'site_id' => $site_id));
    }

    public function http_history_add($data)
    {
        return $response = $this->http_request($this->api_host().'/api/crawler/history/add', 'POST', $data);
    }

    public function http_status_check()
    {
        return $this->http_request($this->api_host().'/api/crawler/status/update', 'GET');
    } 

    public function http_authorisation_request()
    {
        return $this->http_request($this->api_host().'/api/crawler/authorisation/request', 'GET');
    } 
    
    public function http_sites_pages_avoid($site_id)
    {
        return $this->http_request($this->api_host().'/api/crawler/sites/pages/avoid', 'POST', array('site_id' => $site_id));
    }

    public function http_sites_info_get($site_id)
    {
        return $this->http_request($this->api_host().'/api/crawler/sites/get', 'POST', array('site_id' => $site_id));
    }

    public function http_sites_pages_get($site_id)
    {
        return $this->http_request($this->api_host().'/api/crawler/sites/pages/get', 'POST', array('site_id' => $site_id));
    } 
    
    public function http_sites_pages_items_get($site_page_id)
    {
        return $this->http_request($this->api_host().'/api/crawler/sites/pages/items', 'POST', array('site_page_id' => $site_page_id));
    }

    public function http_save_category($data)
    {
        return $this->http_request($this->api_host().'/api/category/save', 'POST', $data);
    }
    
    public function retrieve_page_items($site_id, $page_type_id)
    {
        // Try to get Page Items information from cache
        if (!Cache::has('pages.items.'.$site_id.'.'.$page_type_id))
        {
            // Go get page items information
            if ($extract_items = $this->http_sites_pages_items_get($page_type_id))
            {
                Cache::put('pages.items.'.$site_id.'.'.$page_type_id, json_encode($extract_items), Carbon::now()->addMinutes(10)); 
                return $extract_items;                                           
            }
            else
            {
                throw new Exception($extract_items);
            }                          
        }
        else
        {
            return json_decode(Cache::get('pages.items.'.$site_id.'.'.$page_type_id));
        }
    }

    public function retrieve_site_info($site_id)
    {
        // Try to get Page Items information from cache
        $cache_name = 'site.'.$site_id;

        if (!Cache::has($cache_name))
        {
            // Go get page items information
            if (!is_a( $result = $this->http_sites_info_get($site_id), 'Exception'))
            {
                Cache::put($cache_name, json_encode($result), Carbon::now()->addMinutes(10)); 
                return $result;                                           
            }
            else
            {
                var_dump($result); exit;
                throw new Exception($result);
            }                          
        }
        else
        {
            return json_decode(Cache::get($cache_name));
        }
    }

    public function retrieve_pages_avoid($site_id)
    {
        // Try to get Page Items information from cache
        $cache_name = 'pages.avoid.'.$site_id;

        if (!Cache::has($cache_name))
        {
            // Go get page items information
            if (!is_a( $result = $this->http_sites_pages_avoid($site_id), 'Exception'))
            {
                Cache::put($cache_name, json_encode($result), Carbon::now()->addMinutes(10)); 
                return $result;                                           
            }
            else
            {
                throw new Exception($result);
            }                          
        }
        else
        {
            return json_decode(Cache::get($cache_name));
        }
    }

    public function retrieve_pages($site_id)
    {
        // Try to get Page Items information from cache
        $cache_name = 'pages.'.$site_id;

        if (!Cache::has($cache_name))
        {
            // Go get page items information
            if (!is_a( $result = $this->http_sites_pages_get($site_id), 'Exception'))
            {
                Cache::put($cache_name, json_encode($result), Carbon::now()->addMinutes(10)); 
                return $result;                                           
            }
            else
            {
                throw new Exception($result);
            }                          
        }
        else
        {
            return json_decode(Cache::get($cache_name));
        }
    }
    
    public function request_url($url)
    {
        try{
            $client = PhantomJSClient::getInstance();
        
            $client->getEngine()->setPath(base_path('bin/phantomjs'));
            //$client->getEngine()->debug(true);

            $client->isLazy();

            $request = $client->getMessageFactory()->createRequest();
            $response = $client->getMessageFactory()->createResponse();

            $request->addHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0');
            $request->setViewportSize(1600,1024);
            $request->setMethod('GET');
            $request->setUrl($url);
            $request->setTimeout(120000);

            $url_hash = md5($url);

            $client->send($request, $response);

            if($response->getStatus() === 200) 
            {            
                $return = new \stdClass();
                
                $return->body = $response->getContent(); 

                return $return;
            }
            else
            {
                throw new Exception("HTTP Error [".$response->getStatus()."] for ".$url."\n");
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    private function clean_link($url, $site_start_url)
    {
        //echo '<div>----> Site Host: '.parse_url($site_start_url, PHP_URL_HOST).'</div>';
        //echo '<div>----> Link Host: '.parse_url($url, PHP_URL_HOST).'</div>';


        if (parse_url($url, PHP_URL_HOST) !== parse_url($site_start_url, PHP_URL_HOST))
        {
            //echo '<div>----->  hostname match [FAIL]</div>';

            // if the first letter is a forward slash, then form it into a nice URL
            if (substr($url,0,1) == "/")
            {
                //echo '<div>----------> Checking: URL first letter is a forward slash.</div>';

                // Check to see if it already contains the domain name
                if (!strpos($url,parse_url($site_start_url, PHP_URL_HOST)))
                {
                    // Clean up URL
                    if (substr($url,0,1) == "/")
                    {
                        // If starts with "//"
                        if (substr($url,1,2) == "/")
                        {
                            //echo '<div>----------> It doesnt contain the hostname & is a double forward slash.</div>';
                            return "https://".parse_url($site_start_url, PHP_URL_HOST).substr($url,1);
                        }
                        else
                        {
                            //echo '<div>----------> It doesnt contain the hostname & is a single forward slash.</div>';
                            return "https://".parse_url($site_start_url, PHP_URL_HOST).$url;
                        }                     
                    }
                }
                else
                {
                    // Clean up URL
                    if (substr($url,0,1) == "/")
                    {
                        // If starts with "//"
                        if (substr($url,1,2) == "/")
                        {
                            //echo '<div>----------> It does contain the hostname & is a double forward slash.</div>';
                            return "https://".parse_url($site_start_url, PHP_URL_HOST).substr($url,1);
                        }
                        else
                        {
                            //echo '<div>----------> It does contain the hostname & is a single forward slash.</div>';
                            return "https://".parse_url($site_start_url, PHP_URL_HOST).$url;
                        }                     
                    }
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            //echo '<div>----->  hostname match [OK]</div>';
            return $url;
        }
    }

    public function extract_links($html, $pages_to_avoid, $site_start_url)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');
        
        $saved_links = array();
        $deleted_links = array();

        foreach ($links as $link)
        {                                
            //echo '<div>Original Link: '.$link->getAttribute('href').'</div>';

            if ($url = $this->clean_link($link->getAttribute('href'),$site_start_url))
            {
                //echo '<div>-----> Cleaned URL: '.$url.'</div>';
                // Check to see if any should be avoided
                //echo '<div>----------> Checking for pages to avoid.</div>';
                if (count($pages_to_avoid) > 0)
                {
                    //echo '<div>----------> '.count($pages_to_avoid).' pages to avoid... checking.</div>';
                    foreach ($pages_to_avoid as $avoidedPage)
                    {
                        if (!preg_match("/".$avoidedPage->url_pattern."/", $url))
                        {
                            //echo '<div>----------> No pages to avoid found [OK].</div>';
                            //echo '<div>----->  Link <b>SAVED</b></div>';
                            array_push($saved_links, $url);                           
                        }
                        else
                        {
                            //echo '<div>----------> Page to avoid found [FAIL].</div>';
                            //echo '<div>----->  Link <b>DELETED</b></div>';
                            array_push($deleted_links, $url);
                        }
                    }
                }
                else
                {
                    //echo '<div>----------> No pages to avoid.</div>';
                    //echo '<div>----->  Link <b>SAVED</b></div>';
                    array_push($saved_links, $url);
                }
            }
            else
            {
                //echo '<div>----->  Link <b>DELETED</b></div>';
                array_push($deleted_links, $link->getAttribute('href'));
            }
        }

        //echo '<h3>Saved links are as follows:</h3><pre>'; print_r($saved_links); echo '</pre>';
        //echo '<h3>Deleted links are as follows:</h3><pre>'; print_r($deleted_links); echo '</pre>';   
        //exit;
        return $saved_links;
    }

    public function extract_items_from_html($html, $page_type_id, $url, $extract_items)
    {
        // Get Items to extract from HTML

        //echo '<h2>Items to Extract</h2><pre>'; print_r($extract_items); echo '</pre>'; exit;

        $return_elements = array();
                
        foreach ($extract_items as $extract_item)
        {
            //echo '<div><u>Working on finding <b>'.$extract_item->item_name.'</b> ('.$extract_item->identifier.')</u></div>';

            if ($extract_item->identifier_type == "css")
            {
                //echo '<div>-----> Item Type: CSS Selector</div>';
                $converter = new CssSelectorConverter();
                
                $dom = new \DOMDocument;
                @$dom->loadHTML($html);
                
                $xpath = new \DOMXpath($dom);

                $xpath_value = $converter->toXPath($extract_item->identifier);

                $elements = $xpath->query($xpath_value);         

                
                //echo '<div>-----> XPath Value: '.$xpath_value.'</div>';
                //echo '<div>-----> # of elements found: '.$elements->length.'</div>';
                
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
                                $cleanNode = trim(preg_replace('/[^A-Za-z0-9 !@#$%^&*().]/', '', $node->nodeValue));

                                echo '<div>---------------> Node Value: '.$node->nodeValue.' && CleanNode: '.$cleanNode.'</div>';

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
                //echo '<div>-----> Item Type: Regex</div>';
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
}