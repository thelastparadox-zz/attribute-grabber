<?php

namespace App\Library;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Illuminate\Support\Facades\DB;
use Exception;

class Queue
{
    public $connection = [
        'host'      => '192.168.147.128',
        'vhost'     => '/',    // The default vhost is /
        'user'      => 'guest', // The default user is guest
        'password'  => 'guest', // The default password is guest
      ];
      
      public function seed_queue($queue_name)
      {
        $data_array = array (
          0 => array('site_id' => 1, 'url' => 'https://www.homedepot.com/b/Heating-Venting-Cooling-HVAC-Parts-Accessories-Ducting-Venting/N-5yc1vZc4nu?NCNI-5'),
        );
        
        $bunny = new Client($this->connection);
        $bunny->connect();
        $channel = $bunny->channel();
        $channel->queueDeclare($queue_name); // Queue name
    
        foreach ($data_array as $data)
        {
          $channel->publish(json_encode($data), [], '', $queue_name);
        }
      }
      
    public function fetch_from_queue($queue_name)
    {       
        $bunny = new Client($this->connection);
        $bunny->connect();
        $channel = $bunny->channel();

        $message = $channel->get($queue_name);

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

    public function push_to_queue($queue_name, $data)
    {
        $bunny = new Client($this->connection);
        $bunny->connect();
        $channel = $bunny->channel();
        $channel->queueDeclare($queue_name); // Queue name

        $channel->publish(json_encode($data), [], '', $queue_name);
    }
}