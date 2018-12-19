<?php
 namespace app;
 use PDO;
 use PDOException;

 define('BROKER', 'localhost');
 define('PORT', 1883);
 define('CLIENT_ID', "pubclient_" + getmypid());
 
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
     protected $redis;

     /**
      * @var string
      */
     private $url;

     /**
      * @var null|integer
      */
     protected $userRefreshTime = null;


     /**
      * @var bool
      */
     private $useMqtt = false;

     /**
      * @var string
      */
     protected $mqttTopic = '';
     
     public function __construct()
     {
         $this->url = $_SERVER['argv'][2] ?? 0;
         if ($this->url === 'mqtt' || !empty($this->mqttTopic)) {
             $this->useMqtt = true;
         }
         
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
     
     protected function getRefreshTime()
     {
         $this->userRefreshTime = (int)$this->userRefreshTime;
         if (empty($this->userRefreshTime) || $this->userRefreshTime < 0) {
             return self::REFRESH_TIME;
         }
         return $this->userRefreshTime;
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
         if ($value == -127) {
             return false;
         }
         
         $listLen = $this->redis->lLen($listKey);
         if ($listLen === false || $listLen <= self::COMPARE_LIST_LEN) {
             $this->redis->rPush($listKey, $value);
             return $value;
         }

         $lastValues = $this->redis
             ->multi()
             ->lRange($listKey, -1 * self::COMPARE_LIST_LEN, -1)
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
         $delta = $value - $avg;

         $unsignedDelta = $delta < 0 ? $delta * -1 : $delta;
         if ($unsignedDelta > self::MAX_DELTA_TEMP) {
             return false;
         }

//         $this->redis->lPush($listKey, $value);
         return true;
     }
     
     
     protected function saveData(array $data)
     {
     }
     
     protected function initMqtt()
     {
         $client = new \Mosquitto\Client(CLIENT_ID);
//        $client->onConnect('connect');
//        $client->onDisconnect('disconnect');
//        $client->onSubscribe('subscribe');
         $client->onMessage(function ($message) {
             if (!empty($this->mqttTopic) && $this->mqttTopic != $message->topic) {
                 return;
             }
             $this->execute($message->topic, $message->payload);
         });
         $client->connect(BROKER, PORT, 60);
         $client->subscribe('#', 1); // Subscribe to all messages

         $client->loopForever();
     }

     public function init()
     {
         if ($this->useMqtt) {
             $this->initMqtt();
             return;
         }
         while (true) {
             $this->execute();
             sleep($this->getRefreshTime());
         }
     }
     
     
     abstract function execute($topic = '', $msg = '');
 }

