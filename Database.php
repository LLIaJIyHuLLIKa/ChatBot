<?php
    class Database { // Класс для работы с базой данных
        private $host;
        private $db;
        private $dsn;
        private $user;
        private $password;
        private $options;
        private $pdo;
        
        public function __construct() {
            $this->host = 'localhost';
            $this->db = 'id6465400_telebot';
            $this->dsn = "mysql:host=$this->host;dbname=$this->db";
            $this->user = $this->db;
            $this->password = '123456';
            $this->options = [
        	    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        	    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        	    PDO::ATTR_EMULATE_PREPARES => false,
        	    ];
            
            try {
                $this->pdo = new PDO($this->dsn, $this->user, $this->password, $this->options);
            } catch (PDOException $e) {
                $e->getMessage();
            }
        }
        
        public function querySQL($sql, $options = FALSE) { // Метод для выполнения запросов к БД
            try {
                $result = $this->pdo->prepare($sql);
                if($options === FALSE)
                    $result->execute();
                else
                    $result->execute($options);
            } catch (PDOException $e) {
                $e->getMessage();
            }
            
            return $result;
        }
    }
?>