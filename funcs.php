<?php
function buildDatabaseConnection($config) {
  //Connect to DB only here to save response time on other commands
  try {
    $dbConnection = new PDO('mysql:dbname=' . $config['dbname'] . ';host=' . $config['dbserver'] . ';charset=utf8mb4', $config['dbuser'], $config['dbpassword'], array(PDO::ATTR_TIMEOUT => 25));
    $dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    notifyOnException('Database Connection', $config, '', $e);
  }
  return $dbConnection;
}

function notifyOnException($subject, $config, $sql = '', $e = '') {
  global $chatId;
  sendMessage(175933892, 'Bruv, sometin in da database is ded, innit? Check it out G. ' . $e);
  $to = $config['mail'];
  $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
  $headers = 'From: ' . $config['mail'];
  mail($to, $subject, $txt, $headers);
  http_response_code(200);
  die();
}

function sendMessage($chatId, $text, $replyTo = '', $replyMarkup = '') {
  $data = array(
    'disable_web_page_preview' => true,
    'parse_mode' => 'html',
    'chat_id' => $chatId,
    'text' => $text,
    'reply_to_message_id' => $replyTo,
    'reply_markup' => $replyMarkup
  );
  return makeApiRequest('sendMessage', $data);
}

function makeApiRequest($method, $data){
  global $config;
  $url = $config['url'] . $method;

  $options = array(
    'http' => array(
      'ignore_errors' => true,
      'header' => "Content-type: application/json\r\n",
      'method' => 'POST',
      'content' => json_encode($data)
    )
  );
  $context = stream_context_create($options);
  $return = json_decode(file_get_contents($url, false, $context), true);
  if ($return['ok'] !== 1){
    mail($config['mail'], 'Error', print_r($return, true) . "\n" . print_r($options, true) . "\n" . __FILE__);
  }
  return $return['result'];
}

function answerInlineQuery($inlineQueryId, $results, $offset = '0') {
  $data = array(
    'inline_query_id' => $inlineQueryId,
    'results' => $results,
    'offset' => $offset
  );
  return makeApiRequest('answerInlineQuery', $data);
}

function searchForSong($search, $offset = 1) {
  global $dbConnection, $config;
  $offset -= 1;
  $endoffset = $offset + 50;
  $search = '%' . $search . '%';
  try {
    $sql = "SELECT id, artist, title, language FROM mstr WHERE artist LIKE '$search' OR title LIKE '$search' ORDER BY artist, title LIMIT $offset, $endoffset";
    $stmt = $dbConnection->prepare('SELECT id, artist, title, language FROM mstr WHERE artist LIKE :search OR title LIKE :search2 ORDER BY artist, title LIMIT ' . $offset . ', ' . $endoffset);
    $stmt->bindParam(':search', $search);
    $stmt->bindParam(':search2', $search);
    $stmt->execute();
    return $stmt->fetchAll();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  return false;
}
