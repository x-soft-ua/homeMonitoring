<?php

namespace app;

class Ping extends Core
{

    protected $mqttTopic = 'boilerPing';

    const TABLE = 'mqtt_ping';


    public function execute($topic = '', $msg = '')
    {
        $errors = intval($msg);
        
        
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                    date, 
                    board_id,
                    ping
                ) 
                VALUES (
                    NOW(), 
                    0,
                    ' . $errors . '
                )';


        //echo $sql . PHP_EOL;

        $stm = $this->getDb()->prepare($sql);
        $status = $stm->execute();
    }
}