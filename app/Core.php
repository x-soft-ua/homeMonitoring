<?php
 namespace app;
 use PDO;
 use PDOException;

 abstract class Core
 {
     const MYSQL_USER = 'grafana';
     const MYSQL_PASS = 'grafanapass';
     const REFRESH_TIME = 5;

     /**
      * @var PDO
      */
     private $db;

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

