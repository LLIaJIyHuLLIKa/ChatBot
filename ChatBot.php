<?php
    use Telegram\Bot\Api;
    
    class ChatBot { // класс для работы с ботом
        private $telegram; // подключение к Bot API
        private $db; // подключение к БД
        private $updates; // сообщения от клиента
        private $last_update; // последнее сообщение
        private $offset; // смещение (для обработки только последнего сообщения)
        private $update_id; // id сообщения
        private $text; // текст сообщения
        private $chat_id; // id чата
        private $user_id; // id пользователя
        private $first_name; // имя пользователя
        private $last_name; // фамилия пользователя
        private $username; // никнейм пользователя
        private $reply; // ответ бота
        
        public function __construct() {
            $this->telegram = new Api(BOT_TOKEN);
            $this->db = new Database();
            $this->offset = 0;
        }
        
        public function init() { // начало работы бота
            $this->polling();
        }
        
        public function polling() { // цикличная обработка сообщений
            if($this->offset > 0)
    			$this->updates = $this->telegram->getUpdates(['offset' => $this->offset, 'timeout' => 100]);
    		else
    			$this->updates = $this->telegram->getUpdates(['timeout' => 100]);
    			
    		if(count($this->updates) > 0) {
        		$this->last_update = $this->updates[count($this->updates) - 1];
        		
        		$this->update_id = $this->last_update["update_id"];
        		$this->text = $this->last_update["message"]["text"];
        		$this->chat_id = $this->last_update["message"]["chat"]["id"];
        		$this->user_id = $this->last_update["message"]["from"]["id"];
        		$this->first_name = $this->last_update["message"]["from"]["first_name"];
        		$this->last_name = $this->last_update["message"]["from"]["last_name"];
        		$this->username = $this->last_update["message"]["from"]["username"];
        			
        		$this->reply = "";
        		
        		if($this->text) {
        		    if($this->text == "/start") 
        		        $this->start();
        		    else if($this->text == "/help")
        		        $this->help();
        		    else if(stristr($this->text, "/addkey") !== FALSE)
        		        $this->addKey();
        		    else if(stristr($this->text, "/removekey") !== FALSE)
        		        $this->removeKey();
        		    else if($this->text == "/keylist")
        		        $this->keyList();
        		    else
        		        $this->parsing();
        		}
        		
        		$this->offset = $this->update_id + 1;
    		}
    		
    		$this->polling();
        }
        
        public function start() { // обработка команды /start
            $this->reply = "Приветствую вас!\n\n
        					Чтоб увидеть список моих команд, введите /help";
        	
        	$this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $this->reply]);
        }
        
        public function help() { // обработка команды /help
            $this->reply = "Список доступных команд:\n\n
        					/addkey {какое-то слово} - добавить слово для поиска\n
        					/removekey {какое-то слово} - удалить слово для поиска\n
        					/keylist - вывести список всех ваших слов";
        					
        	$this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $this->reply]);
        }
        
        public function addKey() { // обработка команды /addkey
            $words = explode(" ", $this->text);
        			    
        	if(!(count($words) > 1)) {
        	    $this->reply = "Вы не ввели свое слово!";
        	}
        	else {
        	    $key = $words[1];
        			        
                $sql = "INSERT INTO words (user_id, word) VALUES(?, ?)";
            	$this->db->querySQL($sql, array($this->user_id, $key));
            			        
            	$this->reply = "Слово $key успешно добавлено!";
        	}
        			    
        	$this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => $this->reply]);
        }
        
        public function removeKey() { // обработка команды /removekey
            $words = explode(" ", $this->text);
        			    
        	if(!(count($words) > 1)) {
        	    $this->reply = "Вы не выбрали слово для удаления!";
        	}
        	else {
                $key = $words[1];
            	$sql = "SELECT COUNT(*) FROM words WHERE user_id = ? AND word = ?";
            			    
            	$result = $this->db->querySQL($sql, array($this->user_id, $key));
            			    
            	if($result->fetchColumn() > 0) {
            	    $sql = "DELETE FROM words WHERE user_id = ? AND word = ?";
            			            
            	    $this->db->querySQL($sql, array($this->user_id, $key));
            			            
            	    $this->reply = "Слово $key успешно удалено!";
            	}
            	else
            	    $this->reply = "Такого слова нет в вашем словаре.";
            }
        			    
        	$this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => $this->reply]);
        }
        
        public function keyList() { // обработка команды /keylist
            $sql = "SELECT COUNT(*) FROM words WHERE user_id = ?";
        	$this->reply = "<strong>Список ваших слов:</strong>\n\n";
        			    
        	$result = $this->db->querySQL($sql, array($this->user_id));
        			    
        	if($result->fetchColumn() > 0) {
        	    $sql = "SELECT word FROM words WHERE user_id = ?";
        	    $result = $this->db->querySQL($sql, array($this->user_id));
        			            
        	    while($row = $result->fetch()) {
        	        $this->reply .= $row['word']."\n";
        	    }
        	}
        			    
        	$this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => $this->reply, 'parse_mode' => 'HTML']);
        }
        
        public function parsing() { // парсинг сообщений
            $sql = "SELECT COUNT(*) FROM words";
        	
        	$result = $this->db->querySQL($sql);
        	
        	if($result) {
                if($result->fetchColumn() > 0) {
        	        $lowText = mb_strtolower($this->text, mb_detect_encoding($this->text));
        				        
    		        $sql = "SELECT user_id, word FROM words";
        				        
    		        $result = $this->db->querySQL($sql);
        				        
    		        while($row = $result->fetch()) {
    		            $lowWord = mb_strtolower($row['word'], mb_detect_encoding($row['word']));
        				            
    		            if(stristr($lowText, $lowWord) !== FALSE) {
    		                $this->reply = "<strong>$this->first_name $this->last_name (@$this->username)</strong>\n\n$this->text";
        				                
    		                $this->telegram->sendMessage(['chat_id' => $row['user_id'], 'text' => $this->reply, 'parse_mode' => 'HTML']);
    		            }
    		        }
    		    }
    		}
        }
    }
?>