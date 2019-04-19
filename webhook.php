<?php
require_once(__DIR__ . "/funcs.php");
require_once(__DIR__ . "/config.php");
$response = file_get_contents('php://input');
$data = json_decode($response, true);
$dump = print_r($data, true);

if (isset($data['inline_query'])) {
  $dbConnection = buildDatabaseConnection($config);
  $inlineQueryId = $data['inline_query']['id'];
  $senderUserId = $data['inline_query']['from']['id'];
  $search = $data['inline_query']['query'];
  $oldOffset = $data['inline_query']['offset'];

  if ($oldOffset === '' || $oldOffset === 0) {
    $offset = 1;
  } elseif (is_numeric($oldOffset)) {
    $offset = $oldOffset + 50;
  }

  $results = array();
  //Return all polls from $senderUserId

  $songs = searchForSong($search, $offset);
  foreach ($songs as $song) {
    $songId = $song['id'];
    $songArtist = $song['artist'];
    $songTitle = $song['title'];
    $songLanguage = $song['language'];
    $results[] = array(
      'type' => 'article',
      'id' => $songId,
      'title' => "$songTitle",
      'input_message_content' => array(
        'message_text' => "<b>My Song Choice:</b>
Artist: $songArtist
Title: $songTitle
Language: $songLanguage
ID: $songId",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true
      ),
      'description' => "$songArtist
$songLanguage
Zeile 3"
    );
  }
  mail($config['mail'], 'Debug', answerInlineQuery($inlineQueryId, $results, $offset));
  die();
}

$chatId = $data['message']['chat']['id'];
$messageId = $data['message']['message_id'];
$languageCode = $data['message']['from']['language_code'];
if (isset($data['message']['text'])) {
  $text = $data['message']['text'];
}

if (isset($text)) {
  if (substr($text, '0', '1') == '/') {
    $messageArr = explode(' ', $text);
    $command = explode('@', $messageArr[0])[0];
    if ($messageArr[0] == '/start' && isset($messageArr[1])) {
      $command = '/' . $messageArr[1];
    }
  } else {
    die();
  }
  $command = strtolower($command);
  switch ($command) {
    case '/start':
      sendMessage($chatId, 'Hello.');
      break;
    case '/help':
      sendMessage();
      break;
    default:
      sendMessage($chatId, 'Hm, das kenne ich leider nicht...', $messageId);
  }
}