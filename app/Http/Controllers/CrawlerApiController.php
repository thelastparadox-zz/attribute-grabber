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
            $this->check_auth($request->input('token'), $request->getHttpHost());
        }
    }

    private function check_auth($token, $ip)
    {
        $crawler = DB::table('crawlers')->get()->where('host_ip', $ip)->where('auth_token', $token)->first();

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
            )); exit;
        }
    }

    public function history_check(Request $request)
    {
        // Check the shared history for URL
        $history = DB::table('crawler_history')->get()->where('url', $request->input('url'))->first();

        if ($history)
        {
            echo json_encode(array(
                'exists' => true,
                'crawled_at' => $history->crawled_at,
            ));
        }
        else
        {
            // Add URL into the history
            DB::table('crawlers')->insert(array(
                'host_ip' => $request->getHttpHost(),
                'status' => 'pending_auth',
                'auth_token' => sha1($request->getHttpHost().rand()),
                'created_at' => date("Y-m-d H:i:s"),
            ));

            echo json_encode(array(
                'exists' => false,
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
                return response()->json(array('auth_result' => 'fail'));
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
        $token = $request->input('token');

        $crawler = DB::table('crawlers')->get()->where('host_ip', $request->getHttpHost())->where('auth_token', $token)->first();

        if ($crawler)
        {
            // Update the updated column for last checked in
            DB::table('crawlers')->where('id', $crawler->id)->update(['updated_at' => date("Y-m-d H:i:s")]);
            
            echo json_encode(array(
                'result' => 'success', 
                'crawl_enabled' => $crawler->crawl_enabled,
            ));
        }
        else
        {
            echo json_encode(array(
                'result' => 'fail',
                'error_code' => 'INVALID_AUTH_CODE',
            ));
        }  
    }
}