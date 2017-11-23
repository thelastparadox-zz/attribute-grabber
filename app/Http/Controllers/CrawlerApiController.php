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
use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;

class CrawlerApiController extends Controller
{
    // Gets next 1 message from the shared queue
    public function queue_get()
    {
        phpinfo(); exit;
        
        $connection = [
            'host'      => '192.168.147.128',
            'vhost'     => '/',    // The default vhost is /
            'user'      => 'guest', // The default user is guest
            'password'  => 'guest', // The default password is guest
        ];
        
        $bunny = new Client($connection);
        $bunny->connect();
        $channel = $bunny->channel();
        $channel->queueDeclare('crawler-queue'); // Queue name
        $channel->publish('Hello world', [], '', 'crawler-queue');

        //$message = $channel->get('crawler-queue');
        
        // Handle message
        
        //$channel->ack($message); // Acknowledge message

        //var_dump($message);
        
    }

    // Pushes a single message to the queue
    public function queue_add()
    {
        
    }
}