<?php

namespace app;


class BoilerLoad extends Core
{

    const TABLE = 'boiler_load';

    const VALUES_COMPARE_LEN = 15;
    const MIDDLE = 7;
    
    const MAX = 80;
    const MIN = 0;
    

    protected $userRefreshTime = 10;
    protected $mqttTopic = 'boilerLoad';


    /**
     * @param string $topic
     * @param string $msg
     * @return bool|void
     */
    public function execute($topic = '', $msg = '')
    {

        try {
            $stm = $this->getDb()->query('SELECT temp_out FROM temp_boiler ORDER BY date DESC LIMIT 1');
            if ($stm) {
                $curTemp = $stm->fetchColumn(0);
            }
        } catch (\Exception $e) {}
        if (empty($curTemp) || $curTemp < 45) {
            echo 'Low temperature: ' . $curTemp . PHP_EOL;
            return;            
        }
  
        $value = (float)$msg;


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
            return;
        }
        
        sort($lastValues[0]);
        $valuesMap = $lastValues[0];
        $avg = $valuesMap[self::MIDDLE];

        echo $avg . PHP_EOL;
        
        if ($avg > 3000) {
            $avg = 0;
        } elseif ($avg > self::MAX) {
            return;
        }
        
        //$load = 100 - round($avg / (self::MAX - self::MIN) * 100);
        
        echo 'Cur = ' . $value . '; Avg = ' . $avg . PHP_EOL; 
        
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                    `date`, 
                    `boiler_load`
                ) 
                VALUES (
                    NOW(), 
                    ' . (int)$avg . '
                )';

//
        echo $sql . PHP_EOL;

        $stm = $this->getDb()->prepare($sql);
        $status = $stm->execute();

        //echo date('[Y-m-d H:i:s]') . ' => ' . $tempOut . '[' . $status . ']' . PHP_EOL;
    }
}