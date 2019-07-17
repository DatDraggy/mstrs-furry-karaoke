<?php
require_once(__DIR__ . "/funcs.php");
require_once(__DIR__ . "/config.php");
require_once('/var/libraries/composer/vendor/autoload.php');
//^ guzzlehttp
$response = file_get_contents('php://input');
$data = json_decode($response, true);
$dump = print_r($data, true);

if (isset($data['inline_query'])) {
  $dbConnection = buildDatabaseConnection($config);
  $inlineQueryId = $data['inline_query']['id'];
  $senderUserId = $data['inline_query']['from']['id'];
  $search = $data['inline_query']['query'];
  $newOffset = $data['inline_query']['offset'];

  if ($newOffset === '' || $newOffset === 0) {
    $offset = 0;
  } else {
    $offset = $newOffset;
  }

  $results = array();

  $songs = searchForSong($search, $offset);
  foreach ($songs as $song) {
    $songId = $song['id'];
    $songArtist = $song['artist'];
    $songTitle = $song['title'];
    $songLanguage = $song['language'];
    $songTags = $song['tags'];
    $results[] = array(
      'type' => 'article',
      'id' => $songId,
      'title' => "$songTitle",
      'input_message_content' => array(
        'message_text' => "<b>Mein Songwahl:</b>
Künstler: $songArtist
Titel: $songTitle
Sprache: $songLanguage
ID: $songId",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true
      ),
      'description' => "$songArtist
$songLanguage
$songTags"
    );
  }
  answerInlineQuery($inlineQueryId, $results, $offset);
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
      sendMessage($chatId, 'Hallo. Ich bin Mstr\'s Karaoke Bot!
Durch mich kannst du eine Song suchen und ihn direkt teilen.
Brauchst du Hilfe? /hilfe');
      break;
    case '/hilfe':
      sendMessage($chatId, 'Die Suche funktioniert so, wie die @gif-Suche von Telegram. 
Um die Suche zu starten, gebe unten in das Textfeld <code>@MstrFurryKaraoke_bot</code> ein und dahinter einen Suchbegriff.

Hier ist eine Liste aller Tags:
/anime
/bollywood
/cartoon
/disney
/movie
/comedy
/manga
/musical
/opera
/series
/game
/visualNovel

Um eine Liste aller Songs zu sehen, folge bitte diesem Link: <a href="https://drgn.li/karaoke">drgn.li/karaoke</a>');
      break;
    default:
      if (array_key_exists($command, $tags)){
        $dbConnection = buildDatabaseConnection($config);
        $songs = getSongsByTag($tags[$command]);
        sendMessage($chatId, "Songs mit dem Tag '$command':
$songs");
      } else{
        sendMessage($chatId, 'Hm, das kenne ich leider nicht...', $messageId);
      }
  }
}
