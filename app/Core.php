<?php
 namespace app;
 use PDO;
 use PDOException;

 abstract class Core
 {
     const MYSQL_USER = 'grafana';
     const MYSQL_PASS = 'grafanapass';
     const REFRESH_TIME = 5;

     const REDIS_HOST = '127.0.0.1';
     const REDIS_PORT = 6379;

     const MAX_DELTA_TEMP = 10;
     const COMPARE_LIST_LEN = 3;

     /**
      * @var PDO
      */
     private $db;

     /**
      * @var \Redis
      */
     private $redis;

     /**
      * @var string
      */
     private $url;
     
     public function __construct()
     {
         $this->url = $_SERVER['argv'][1] ?? 0;
         if (empty($this->url)) {
             throw new \Exception('Url parameter not specified');
         }
         try {
             $this->db = new PDO('mysql:host=localhost;dbname=grafana', self::MYSQL_USER, self::MYSQL_PASS);
             $this->redis = new \Redis();
             $this->redis->pconnect(self::REDIS_HOST, self::REDIS_PORT);
         } catch (PDOException $e) {
             print "Error!: " . $e->getMessage() . "<br/>";
             die();
         }
     }

     /**
      * @return string
      */
     protected function getUrl() : string
     {
         return $this->url;
     }

     /**
      * @return PDO
      */
     protected function getDb() : PDO
     {
         return $this->db;
     }

     protected function checkValue($key, $value)
     {
         $listKey = 'last_' . $key;
         $listLen = $this->redis->lLen($listKey);
         if ($listLen === false || $listLen <= self::COMPARE_LIST_LEN) {
             $this->redis->lPush($listKey, $value);
             return $value;
         }

         $lastValues = $this->redis
             ->multi()
             ->lRange($listKey, -1 * self::COMPARE_LIST_LEN, -1)
             ->lPop($listKey)
             ->lPush($listKey, $value)
             ->exec();

         if (empty($lastValues[0])) {
             return false;
         }
         $avg = 0;
         foreach ($lastValues[0] as $lastValue) {
             $avg += $lastValue;
         }

         $avg = $avg / self::COMPARE_LIST_LEN;
         $delta = $value - $avg;

         $unsignedDelta = $delta < 0 ? $delta * -1 : $delta;
         if ($unsignedDelta > self::MAX_DELTA_TEMP) {
             return false;
         }

         $this->redis->lPush($listKey, $value);
         return true;
     }
     
     
     protected function saveData(array $data)
     {
     }

     public function init()
     {   
        while (true) {
            $this->execute();
            sleep(self::REFRESH_TIME);
        }
     }
     
     
     abstract function execute();
 }

