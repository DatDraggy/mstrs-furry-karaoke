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

  $results = array();
  //Return all polls from $senderUserId
  $polls = searchForSong($search);
  foreach ($polls as $poll) {
    $songId = $poll['id'];
    $songArtist = $poll['artist'];
    $songTitle = $poll['title'];
    $results[] = array(
      'type' => 'article',
      'id' => $songId,
      'title' => "$songTitle",
      'input_message_content' => array(
        'message_text' => "<b>My Song Choice:</b>
ID: $songId
Artist: $songArtist
Title: $songTitle",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true
      ),
      'description' => $songArtist
    );
  }
  answerInlineQuery($inlineQueryId, $results);
  die();
}