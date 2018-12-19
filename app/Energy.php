<?php

namespace app;

class Energy extends Core
{

    const TABLE = 'energy';

    const COLUMN_VOLTAGE = 'u';
    const COLUMN_AMPERAGE = 'i';
    const COLUMN_POWER = 'p';
    const COLUMN_POWER_HOUR = 'ph';

    private static $sensorMap = [
        self::COLUMN_VOLTAGE => 0,
        self::COLUMN_AMPERAGE => 1,
        self::COLUMN_POWER_HOUR => 3,
        self::COLUMN_POWER => 2,
    ];
    
    protected $userRefreshTime = 15;

    public function execute($topic = '', $msg = '')
    {
        $tempJson = file_get_contents($this->getUrl());
        echo date('Y-m-d H:i:s') . ": $tempJson\n"; 
        if ($tempJson === false) {
            echo 'Invalid response from controller' . PHP_EOL;
            return;
        }

        $tempJsonArr = json_decode($tempJson, true);

        
        $sensorsTemp = [];
        $invalidValues = false;
        foreach (self::$sensorMap as $column => $sensorId) {
            if (!isset($tempJsonArr[$sensorId]) ||
                !is_numeric($tempJsonArr[$sensorId]) /*||
                !$this->checkValue(__CLASS__ . '::' . $column, $tempJsonArr[$sensorId]) */) {
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