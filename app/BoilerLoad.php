<?php

namespace app;

class BoilerLoad extends Core
{

    const TABLE = 'boiler_load';

    const VALUES_COMPARE_LEN = 6;
    
    const MAX = 81;
    const MIN = 0;

    protected $userRefreshTime = 15;


    public function execute()
    {
        try {
            $stm = $this->getDb()->query('SELECT temp_out FROM temp_boiler ORDER BY date DESC LIMIT 1');
            if ($stm) {
                $curTemp = $stm->fetchColumn(0);
            }
        } catch (\Exception $e) {}
        if (empty($curTemp) || $curTemp < 50) {
            //echo 'Low temperature' . PHP_EOL;
            return;            
        }
        
        $tempJson = file_get_contents($this->getUrl());
        
        echo $tempJson . PHP_EOL;
        if ($tempJson === false) {
            echo 'Invalid response from controller' . PHP_EOL;
            return;
        }

        $tempJsonArr = json_decode($tempJson, true);

        $value = $tempJsonArr[0];


        $listKey = 'boiler_load';
        $listLen = $this->redis->lLen($listKey);
        if ($listLen === false || $listLen <= self::VALUES_COMPARE_LEN) {
            $this->redis->rPush($listKey, $value);
            return;
        }

        $lastValues = $this->redis
            ->multi()
            ->lRange($listKey, -1 * self::VALUES_COMPARE_LEN, -1)
            ->lPop($listKey)
            ->rPush($listKey, $value)
            ->exec();

        if (empty($lastValues[0])) {
            return false;
        }
        $avg = 0;
        foreach ($lastValues[0] as $lastValue) {
            $avg += $lastValue;
        }

        $avg = $avg / self::COMPARE_LIST_LEN;


        if ($avg > 3000) {
            $avg = 0;
        } elseif ($avg > self::MAX) {
            return;
        }

        $loadPercent = 100 - round($avg / (self::MAX - self::MIN) * 100);
        
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                    `date`, 
                    `boiler_load`
                ) 
                VALUES (
                    NOW(), 
                    ' . (int)$loadPercent . '
                )';


        echo $sql . PHP_EOL;

        $stm = $this->getDb()->prepare($sql);
        $status = $stm->execute();

        //echo date('[Y-m-d H:i:s]') . ' => ' . $tempOut . '[' . $status . ']' . PHP_EOL;
    }
}