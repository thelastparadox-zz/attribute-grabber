<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;

class CrawlerApiController extends Controller
{
    // Input vars are: $url
    // Return: true if exists, false if not

    public function __construct(Request $request) 
    {
        if (!$request->is('api/crawler/authorisation/*') && !$request->is('api/crawler/status/*'))
        {
            $this->check_auth($request->query('token'), $request->getHttpHost());
        }
    }

    public function get_crawler_info($ip, $token)
    {
        return DB::table('crawlers')->get()->where('host_ip', $ip)->where('auth_token', $token)->first();
    }

    private function check_auth($token, $ip)
    {
        $crawler = $this->get_crawler_info($ip, $token);

        if ($crawler)
        {
            // Update the updated column for last checked in
            DB::table('crawlers')->where('id', $crawler->id)->update(['updated_at' => date("Y-m-d H:i:s")]);
        }
        else
        {
            echo response()->json(array(
                'result' => 'fail',
                'error_code' => 'INVALID_AUTH_CODE',
            ));
        }
    }

    public function history_check(Request $request)
    {
        if ($request->input('url') && $request->input('site_id'))
        {
            // Check the shared history for URL
            $history = DB::table('crawler_history')->get()->where('url', $request->input('url'))->first();

            if ($history)
            {
                return response()->json(array(
                    'exists' => true,
                    'crawled_at' => $history->crawled_at,
                ));
            }
            else
            {
                $crawler = $this->get_crawler_info($request->getHttpHost(), $request->query('token'));

                // Add URL into the history
                DB::table('crawler_history')->insert(array(
                    'crawler_id' => $crawler->id,
                    'url' => $request->input('url'),
                    'site_id' => $request->input('site_id'),
                    'crawled_at' => date("Y-m-d H:i:s"),
                ));

                return response()->json(array(
                    'exists' => false,
                ));
            }
        }
        else
        {
            return response()->json(array(
                'error_code' => 'NO_INPUT_DETECTED',
                'error_message' => 'Please make sure the values for url & site_id are sent in the POST request.'
            ));
        }
    }

    public function history_add(Request $request)
    {

    }

    public function authorisation_request(Request $request)
    {
        // Check to see if crawler is registered in the DB
        $crawler = DB::table('crawlers')->get()->where('host_ip', $request->getHttpHost())->first();
        
        if ($crawler)
        {
            // Update the updated column for last checked in
            DB::table('crawlers')->where('id', $crawler->id)->update(['updated_at' => date("Y-m-d H:i:s")]);
            
            if ($crawler->authorised == false)
            {
                return response()->json(array('auth_result' => 'fail', 'error_code' => 'INVALID_AUTH_CODE'));
            }
            else
            {
                return response()->json(array('auth_result' => 'success', 'token' => $crawler->auth_token, 'host' => $request->getHttpHost()));
            }
        }
        else
        {
            // Add Crawler to Database
            DB::table('crawlers')->insert(array(
                'host_ip' => $request->getHttpHost(),
                'status' => 'pending_auth',
                'auth_token' => sha1($request->getHttpHost().rand()),
                'created_at' => date("Y-m-d H:i:s"),
            ));

            return response()->json(array('auth_result' => 'fail'));
        }
    }

    public function status_update(Request $request)
    {
        // Is the token authorised?
        $token = $request->query('token');

        $crawler = DB::table('crawlers')->get()->where('host_ip', $request->getHttpHost())->where('auth_token', $token)->first();

        if ($crawler)
        {
            // Update the updated column for last checked in
            DB::table('crawlers')->where('id', $crawler->id)->update(['updated_at' => date("Y-m-d H:i:s")]);
            
            return response()->json(array(
                'result' => 'success', 
                'crawl_enabled' => $crawler->crawl_enabled,
            ));
        }
        else
        {
            return response()->json(array(
                'result' => 'fail',
                'error_code' => 'INVALID_AUTH_CODE',
            ));
        }  
    }

    public function sites_pages_avoid_get(Request $request)
    {
        if ($request->input('site_id'))
        {
            $pages = DB::table('sites_pages_avoid')->get()->where('site_id', $request->input('site_id'));

            if ($pages)
            {
                return response()->json($pages);
            }
        }
        else
        {
            return response()->json(array(
                'error_code' => 'NO_INPUT_DETECTED',
                'error_message' => 'Please make sure the value site_id is sent in the POST request.'
            ));
        }
    }

    public function sites_pages_items_get(Request $request)
    {
        if ($request->input('site_page_id'))
        {
            $pages = DB::table('sites_pages_items')
                ->where('site_page_id', $request->input('site_page_id'))
                ->get();

            if ($pages)
            {
                return response()->json($pages);
            }
        }
        else
        {
            return response()->json(array(
                'error_code' => 'NO_INPUT_DETECTED',
                'error_message' => 'Please make sure the value site_id is sent in the POST request.'
            ));
        }
    }

    public function sites_info_get(Request $request)
    {
        if ($request->input('site_id'))
        {
            $site = DB::table('sites')->get()->where('id', $request->input('site_id'))->first();

            if ($site)
            {
                return response()->json($site);
            }
            else
            {
                return response()->json(array(
                    'error_code' => 'NO_SITE_FOUND',
                    'error_message' => 'No site by ID ['.$request->input('site_id').'] found - please ensure a valid site_id is sent in the POST request.'
                ));
            }
        }
        else
        {
            return response()->json(array(
                'error_code' => 'NO_INPUT_DETECTED',
                'error_message' => 'Please make sure the value site_id is sent in the POST request.'
            ));
        }
    }

    public function sites_pages_get(Request $request)
    {
        if ($request->input('site_id'))
        {
            $pages = DB::table('sites_pages')->get()->where('site_id', $request->input('site_id'));

            if ($pages)
            {
                return response()->json($pages);
            }
            else
            {
                return response()->json(array(
                    'error_code' => 'NO_SITE_FOUND',
                    'error_message' => 'No site by ID ['.$request->input('site_id').'] found - please ensure a valid site_id is sent in the POST request.'
                ));
            }
        }
        else
        {
            return response()->json(array(
                'error_code' => 'NO_INPUT_DETECTED',
                'error_message' => 'Please make sure the value site_id is sent in the POST request.'
            ));
        }
    }

    public function category_save(Request $request)
    {
        if ($request->input('site_id') && $request->input('breadcrumbs'))
        {          
            $input = $request->only(['category_name', 'category_id', 'breadcrumbs', 'url', 'site_id']);
            
            DB::table('categories')->insert($input);

            return response('OK');
        }
        else
        {
            return response()->json(array(
                'error_code' => 'NO_INPUT_DETECTED',
                'error_message' => 'Please make sure the values of site_id & breadcrumbs are sent in the POST request.'
            ));
        }
    }
}