<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use App\Library\Crawler;
use App\Library\Queue;

class CronController extends Controller
{
    public $api_host = 'http://192.168.147.128';

    public function cron_main(Request $request)
    {
        // Verify if the Worker has been authorised and has the token stored locally      
        if (!Storage::disk('local')->exists('config/autorisation.token'))
        {
            $this->request_authorisation();
        }
        else
        {
            $token = Storage::disk('local')->get('config/autorisation.token');

            $status = $this->check_status($token);

            //var_dump($status); 
            //var_dump(property_exists($status, "error_code"));
            //exit;

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
                    $this->work_crawl_queue();
                }
                else
                {
                    echo 'Hang tight, crawl is disabled.';
                }
            }  
        }
    }

    private function check_status($token)
    {
        $client = new Client(['http_errors' => false]);
        try {
            $res = $client->request('POST', $this->api_host."/api/crawler/status/update",['json' => [
                'token' => $token,
            ]]);
        } catch (RequestException $e) {
            echo "Error"; exit;
        }
        
        if (!isset($e))
        {
            if ($res->getStatusCode() == '200')
            {
                $response = json_decode($res->getBody());

                return $response;
            }
            else
            {
                $return = new \stdClass();
                
                $return->error_message = "Interface returned a status code of ".$res->getStatusCode();
                $return->error_code = "INTERFACE_ERROR";
                
                return $return;
            }      
        }
        else
        {
            return "Error";
        }
    }

    private function request_authorisation()
    {
        //echo '<div>Requesting auth...</div>';
        $client = new Client(['http_errors' => false]);
        try {
            $res = $client->request('GET', $this->api_host."/api/crawler/authorisation/request");
        } catch (RequestException $e) {
            echo "Error"."\r\n"; exit; //$e->getRequest();
        }
        
        if (!isset($e))
        {
            //echo '<div>Response code: '.$res->getStatusCode().'</div>';
            if ($res->getStatusCode() == '200')
            {
                $response = json_decode($res->getBody());

                if ($response->auth_result == "success")
                {
                    Storage::disk('local')->put('config/autorisation.token', $response->token);

                    echo 'Authorised.';
                }
                else
                {
                    echo 'Nothing to do yet (awaiting authorisation from server).'."\r\n";
                }
            }
            else
            {
                echo 'Theres some kind of error with the interface.\n\n'.$res->getBody()."\r\n";
            }      
        }
    }

    private function work_crawl_queue()
    {
        $total_processed = 0;
        
        $queue = new Queue();
        $queue->seed_crawl_queue();

        for ($i = 1; $i <= 10; $i++)
        {
            $message = $queue->fetch_from_queue();

            if ($message)
            {
                $page = json_decode($message->content);
                $total_processed++;
                $crawler = new Crawler();
                $saved_links = $crawler->process_url($page->url, $page->site_id);
                
                if ($saved_links)
                {
                    print_r($saved_links);
                }
                else
                {
                    echo 'Page already checked';
                }
            }
            else
            {
                echo 'End of Queue';
                break;
            }
        }

        echo '<div>Total Messages Processed: '.$total_processed.'</div>';
    }
}