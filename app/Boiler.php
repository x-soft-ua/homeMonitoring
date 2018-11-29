<?php

namespace app;

class Boiler extends Core
{

    const TABLE = 'temp_boiler';

    public function execute()
    {
        $tempOut = file_get_contents($this->getUrl());
        if ($tempOut === false) {
            echo 'Invalid response from controller' . PHP_EOL;
            return;
        }
        
        if ($this->checkValue('boiler', $tempOut)) {
            $stm = $this->getDb()->prepare('INSERT INTO ' . self::TABLE . ' (date, temp_out) VALUES (NOW(), :temp_out)');
            $status = $stm->execute([
                'temp_out' => $tempOut
            ]);
        } else {
            echo 'Invalid value: ' . $tempOut . PHP_EOL;
        }
        
        //echo date('[Y-m-d H:i:s]') . ' => ' . $tempOut . '[' . $status . ']' . PHP_EOL;
    }
}