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
  $songs = searchForSong($search);
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
ID: $songId
Artist: $songArtist
Title: $songTitle",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true
      ),
      'description' => "$songArtist
$songLanguage"
    );
  }
  answerInlineQuery($inlineQueryId, $results);
  die();
}

$chatId = $data['message']['chat']['id'];
if (isset($data['message']['text'])) {
  $text = $data['message']['text'];
}
die();
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
  switch (true) {
    case ($command === '/start'):
      sendMessage($chatId, 'Hello! I\'m the Summerbo.at Bot.
To get a command overview, send /help.');
      break;
    case ($command === '/help'):
      sendMessage($chatId, 'Applying for Volunteer: /apply
Location: /venue
Badge pickup: /badge');
      break;
    case ($command === '/id'):
      sendMessage($chatId, $chatId . ' ' . $senderUserId);
      break;
    case ($command === '/apply'):
      if (!empty($messageArr[1]) && $messageArr[0] !== '/start') {
        $dbConnection = buildDatabaseConnection($config);
        $application = explode(' ', $text, 2)[1];
        $saveName = $senderName;
        if ($senderUsername !== NULL) {
          $saveName = $senderUsername;
        }
        if (saveApplication($chatId, $saveName, $application)) {
          $replyMarkup = array(
            'inline_keyboard' => array(
              array(
                array(
                  'text' => 'Handled',
                  'callback_data' => $chatId . '|handled|' . $saveName . '|0'
                )
              )
            )
          );
          sendStaffNotification($chatId, "<b>New application from </b><a href=\"tg://user?id=$chatId\">$saveName</a>:
$application", $replyMarkup);
          sendMessage($chatId, 'Thank you! Your application will be reviewed soon.');
          mail('team@summerbo.at', 'New Application!', "By: $saveName
Message: $application");
        } else {
          sendMessage($chatId, 'Sorry, something went wrong. Perhaps you already applied?');
        }
      } else {
        sendMessage($chatId, '<b>How to apply as a volunteer:</b>
Write <code>/apply</code> with a little bit about yourself and experiences behind it.
Example: <code>/apply Hello, I\'m Dragon!</code>');
      }
      die();
      break;
    case ($command === '/reg' && isTelegramAdmin($chatId)):
      if (isset($messageArr[1])) {
        if ($messageArr[1] === 'status') {
          if (isset($messageArr[2])) {
            $dbConnection = buildDatabaseConnection($config);
            $details = getRegDetails($messageArr[2], 'id, nickname, status, approvedate');
            $approvedate = date('Y-m-d', $details['approvedate']);
            sendMessage($chatId, "
Regnumber: {$details['id']}
Nickname: {$details['nickname']}
Status: {$details['status']}
Approved: $approvedate");
          } else {
            sendMessage($chatId, 'Please supply a regnumber.');
          }
        }
      }
      break;
    case ($command === '/blacklist' && isTelegramAdmin($chatId)):
      //ToDo: TBD
      break;
    case ($command == '/getunconfirmed' && isTelegramAdmin($chatId)):
      $dbConnection = buildDatabaseConnection($config);
      requestUnapproved($chatId);
      break;
    case ($command === '/payment' && isTelegramAdmin($chatId)):
      if (isset($messageArr[1])) {
        $dbConnection = buildDatabaseConnection($config);
        if ($messageArr[1] === 'status') {
          if (isset($messageArr[2])) {
            $details = getPaymentDetails($messageArr[2], 'users.id, approvedate, amount, topay');
            if ($details === false) {
              sendMessage($chatId, 'No Payments');
            } else {
              foreach ($details as $detail) {
                $payByDate = date('Y-m-d', strtotime('+2 weeks', $details['approvedate']));
                sendMessage($chatId, "
Regnumber: {$detail['id']}
Until: $payByDate
Paid: {$detail['amount']}
To pay: {$detail['topay']}");
              }
            }
          } else {
            sendMessage($chatId, 'Please supply a regnumber.');
          }
        } else if (is_numeric($messageArr[1])) {
          if (isset($messageArr[2])) {
            $status = (approvePayment($messageArr[2], $senderUserId, $messageArr[1]) ? 'yes' : 'no');
            sendMessage($chatId, 'Updated. Payment completed: ' . $status);
          } else {
            sendMessage($chatId, 'Please supply a regnumber.');
          }
        } else {
          sendMessage($chatId, 'The given amount is not numeric.');
        }
      } else {
        sendMessage($chatId, 'Usage:
<code>/payment</code> <b>amount</b> <b>regnumber</b>');
      }
      break;
    case ($command === '/venue'):
      sendVenue($chatId, 52.473208, 13.458217, 'Estrel Sommergarten', 'Ziegrastraße 44, 12057 Berlin');
      break;
    case ($command === '/badge'):
      sendMessage($chatId, 'On the day of the party you can pick up the badge inside the Estrel Hotel during the afternoon or in the evening in the Biergarten near the boat. Please make sure you bring your ID or Passport with you. The badge is your entrance to the party so please do not lose it. There will be no tickets sold on the day itself.');
      break;
    default:
      sendMessage($chatId, 'Hello! I\'m the Summerbo.at Bot.
To get a command overview, send /help.');
      break;
  }
}