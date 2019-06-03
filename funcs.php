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
  if (strlen($text) > 4096) {
    sendMessage($chatId, substr($text, 0, 4096), $replyTo, $replyMarkup);
    sendMessage($chatId, substr($text, 4096), $replyTo, $replyMarkup);
  } else {
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
}

function makeApiRequestB($method, $data) {
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
  if ($return['ok'] != 1) {
    mail($config['mail'], 'Error', print_r($return, true) . "\n" . print_r($options, true) . "\n" . __FILE__);
  }
  return $return['result'];
}

function makeApiRequest($method, $data) {
  global $config, $client;
  if (!($client instanceof \GuzzleHttp\Client)) {
    $client = new \GuzzleHttp\Client(array(
      'headers' => [
        'Content-Type' => 'application/json'
      ]
    ));
  }
  try {
    //    $response = $client->post($method, array('query' => $data));
    $response = $client->request('POST', $config['url'] . $method, array('content' => json_encode($data)));
  } catch (\GuzzleHttp\Exception\BadResponseException $e) {
    $body = $e->getResponse()->getBody();
    mail($config['mail'], 'Test', print_r($body->getContents(), true));
  }
  return json_decode($response->getBody(), true)['result'];
}

function answerInlineQuery($inlineQueryId, $results, $offset) {
  $data = array(
    'inline_query_id' => $inlineQueryId,
    'results' => $results,
    'next_offset' => $offset + 50
  );
  return makeApiRequest('answerInlineQuery', $data);
}

function searchForSong($search, $offset) {
  global $dbConnection, $config;
  $search = '%' . $search . '%';
  try {
    $sql = "SELECT id, artist, title, language, tags FROM mstr WHERE artist LIKE '$search' OR title LIKE '$search' ORDER BY artist, title LIMIT $offset, 50";
    $stmt = $dbConnection->prepare('SELECT id, artist, title, language, tags FROM mstr WHERE artist LIKE :search OR title LIKE :search2 ORDER BY artist, title LIMIT ' . $offset . ', 50');
    $stmt->bindParam(':search', $search);
    $stmt->bindParam(':search2', $search);
    $stmt->execute();
    return $stmt->fetchAll();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  return false;
}

function getSongsByTag($tag) {
  global $dbConnection, $config;

  try {
    $sql = "SELECT artist, title, language FROM mstr WHERE tags = '$tag'";
    $stmt = $dbConnection->prepare('SELECT artist, title, language FROM mstr WHERE tags = :tag');
    $stmt->bindParam(':tag', $tag);
    $stmt->execute();
    $rows = $stmt->fetchAll();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  $songs = '';
  foreach ($rows as $row) {
    $artist = $row['artist'];
    $title = $row['title'];
    if (!empty($row['language'])) {
      $title .= '
' . $row['language'];
    }
    $songs .= "
$artist
$title
";
  }
  return $songs;
}
