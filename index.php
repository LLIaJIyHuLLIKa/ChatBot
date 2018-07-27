<?php
	require_once(dirname(__FILE__).'/vendor/autoload.php');
	require_once(dirname(__FILE__).'/tokens.php');
	
	use Telegram\Bot\Api;
	
	$telegram = new Api(BOT_TOKEN);
	
	$offset = 0;
	
	$host = 'localhost';
	$db = 'id6465400_telebot';
	$user = 'id6465400_telebot';
	$password = '123456';
	
	$dsn = "mysql:host=$host;dbname=$db";
	
	$options = [
	    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	    PDO::ATTR_EMULATE_PREPARES => false,
	    ];
	    
	try {
    	$pdo = new PDO($dsn, $user, $password, $options);
    		
    	while(true) {
    		if($offset > 0)
    			$updates = $telegram->getUpdates(['offset' => $offset, 'timeout' => 100]);
    		else
    			$updates = $telegram->getUpdates(['timeout' => 100]);
    		
    		if(count($updates) > 0) {
        		$last_update = $updates[count($updates) - 1];
        		
        		$id = $last_update["update_id"];
        		$text = $last_update["message"]["text"];
        		$chat_id = $last_update["message"]["chat"]["id"];
        		$user_id = $last_update["message"]["from"]["id"];
        		$first_name = $last_update["message"]["from"]["first_name"];
        		$last_name = $last_update["message"]["from"]["last_name"];
        		$username = $last_update["message"]["from"]["username"];
        			
        		$reply = "";
        			
        		if($text) {
        			if($text == "/start") {
        				$reply = "Приветствую вас!\n\n
        					Чтоб увидеть список моих команд, введите /help";
        					
        				$telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply]);
        			}
        			
        			else if($text == "/help") {
        			    $reply = "Список доступных команд:\n\n
        					/addkey {какое-то слово} - добавить слово для поиска\n
        					/removekey {какое-то слово} - удалить слово для поиска\n
        					/keylist - вывести список всех ваших слов";
        					
        				$telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply]);
        			}
        			
        			else if(stristr($text, "/addkey") !== FALSE) {
        			    $words = explode(" ", $text);
        			    
        			    if(!(count($words) > 1)) {
        			        $reply = "Вы не ввели свое слово!";
        			    }
        			    else {
        			        $key = $words[1];
        			        
            			    $result = $pdo->prepare("INSERT INTO words (user_id, word) VALUES(?, ?)");
            			    $result->execute(array($user_id, $key));
            			        
            			    $reply = "Слово $key успешно добавлено!";
        			    }
        			    
        			    $telegram->sendMessage(['chat_id' => $user_id, 'text' => $reply]);
        			}
        			
        			else if(stristr($text, "/removekey") !== FALSE) {
        			    $words = explode(" ", $text);
        			    
        			    if(!(count($words) > 1)) {
        			        $reply = "Вы не выбрали слово для удаления!";
        			    }
        			    else {
            			    $key = $words[1];
            			    $sql = "SELECT COUNT(*) FROM words WHERE user_id = ? AND word = ?";
            			    
            			    $result = $pdo->prepare($sql);
            			    $result->execute(array($user_id, $key));
            			    
            			    if($result->fetchColumn() > 0) {
            			        $sql = "DELETE FROM words WHERE user_id = ? AND word = ?";
            			            
            			        $result = $pdo->prepare($sql);
            			        $result->execute(array($user_id, $key));
            			            
            			        $reply = "Слово $key успешно удалено!";
            			    }
            			    else
            			        $reply = "Такого слова нет в вашем словаре.";
            			}
        			    
        			    $telegram->sendMessage(['chat_id' => $user_id, 'text' => $reply]);
        			}
        			
        			else if($text == "/keylist") {
        			    $sql = "SELECT COUNT(*) FROM words WHERE user_id = ?";
        			    $reply = "<strong>Список ваших слов:</strong>\n\n";
        			    
        			    $result = $pdo->prepare($sql);
        			    $result->execute(array($user_id));
        			    
        			    if($result->fetchColumn() > 0) {
        			        $sql = "SELECT word FROM words WHERE user_id = ?";
        			        $result = $pdo->prepare($sql);
        			        $result->execute(array($user_id));
        			            
        			        while($row = $result->fetch()) {
        			            $reply .= $row['word']."\n";
        			        }
        			    }
        			    
        			    $telegram->sendMessage(['chat_id' => $user_id, 'text' => $reply, 'parse_mode' => 'HTML']);
        			}
        				
        			else {
        				$sql = "SELECT COUNT(*) FROM words";
        				
        				if($result = $pdo->query($sql)) {
        				    if($result->fetchColumn() > 0) {
        				        $lowText = mb_strtolower($text, mb_detect_encoding($text));
        				        
        				        $sql = "SELECT user_id, word FROM words";
        				        
        				        $result = $pdo->query($sql);
        				        
        				        while($row = $result->fetch()) {
        				            $lowWord = mb_strtolower($row['word'], mb_detect_encoding($row['word']));
        				            
        				            if(stristr($lowText, $lowWord) !== FALSE) {
        				                $reply = "<strong>$first_name $last_name (@$username)</strong>\n\n$text";
        				                
        				                $telegram->sendMessage(['chat_id' => $row['user_id'], 'text' => $reply, 'parse_mode' => 'HTML']);
        				            }
        				        }
        				    }
        				}
        			}
        		}
        		
        		$offset = $id + 1;
    		}
    	}
	} catch (PDOException $e) {
	    echo $e->getMessage();
	}
?>