<?php

namespace app;

class Boiler extends Core
{

    protected $mqttTopic = 'boilerTemp';

    const TABLE = 'temp_boiler';

    const COLUMN_INSIDE = 'temp_inside';
    const COLUMN_OUTSIDE = 'temp_outside';
    const COLUMN_BOILER_OUT = 'temp_out';
    const COLUMN_BUFFER_BOTTOM = 'buffer_bottom';
    const COLUMN_BUFFER_TOP= 'buffer_top';
    

    private static $sensorMap = [
        self::COLUMN_INSIDE => 1,
        self::COLUMN_BOILER_OUT => 4,
        self::COLUMN_BUFFER_BOTTOM => 3,
        self::COLUMN_BUFFER_TOP => 0,
        self::COLUMN_OUTSIDE => 2,
    ];
    public function execute($topic = '', $msg = '')
    {
        //$tempJson = file_get_contents($this->getUrl());
        $tempJson = $msg;
        
        echo $msg . PHP_EOL;
        //return;
        if ($tempJson === false) {
            echo 'Invalid response from controller' . PHP_EOL;
            return;
        }

        $tempJsonArr = json_decode($tempJson, true);
        
        $sensorsTemp = [];
        $invalidValues = false;
        foreach (self::$sensorMap as $column => $sensorId) {
            if (!isset($tempJsonArr[$sensorId]) ||
                !is_numeric($tempJsonArr[$sensorId]) ||
                !$this->checkValue(__CLASS__ . '::' . $column, $tempJsonArr[$sensorId])) {
                echo 'Invalid sensor value for ' . $column . PHP_EOL;
                $invalidValues = true;
            }
            $sensorsTemp[$column] = $tempJsonArr[$sensorId];
        }
        
        if ($invalidValues) {
            return;
        }
        
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                    date, 
                    ' . implode(',', array_keys($sensorsTemp)) . '
                ) 
                VALUES (
                    NOW(), 
                    ' . implode(',', $sensorsTemp) . '
                )';
        
        
        //echo $sql . PHP_EOL;
        
        $stm = $this->getDb()->prepare($sql);
        $status = $stm->execute();

        //echo date('[Y-m-d H:i:s]') . ' => ' . $tempOut . '[' . $status . ']' . PHP_EOL;
    }
}