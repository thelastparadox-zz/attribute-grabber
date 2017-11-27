<?php

namespace App\Library;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Illuminate\Support\Facades\DB;

class Queue
{
    public $connection = [
        'host'      => '192.168.147.128',
        'vhost'     => '/',    // The default vhost is /
        'user'      => 'guest', // The default user is guest
        'password'  => 'guest', // The default password is guest
      ];
      
      public function seed_crawl_queue()
      {
        $data_array = array (
          0 => array('site_id' => 1, 'url' => 'https://www.diy.com/'),
        );
        
        $bunny = new Client($this->connection);
        $bunny->connect();
        $channel = $bunny->channel();
        $channel->queueDeclare('crawler-queue'); // Queue name
    
        foreach ($data_array as $data)
        {
          $channel->publish(json_encode($data), [], '', 'crawler-queue');
        }
      }
      
      public function fetch_from_queue()
      {
            
        $bunny = new Client($this->connection);
        $bunny->connect();
        $channel = $bunny->channel();
    
        $message = $channel->get('crawler-queue');

        if ($message)
        {
            $channel->ack($message); // Acknowledge message

            return $message;
        }
        else
        {
            return false;
        }     
      }
      

}