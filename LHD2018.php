<?php
$CONFIG = parse_ini_file("config.ini");

$authorization = "Authorization: Bearer " . $CONFIG['BOT_TOKEN'];


function logToFile($msg) {
  file_put_contents('slack-post.log', $msg.PHP_EOL , FILE_APPEND | LOCK_EX);
}
$slack_request = file_get_contents('php://input');
$params = json_decode($slack_request, true);



function strExists($string, $search) {
  return strpos( strtolower($string), $search ) !== false;
}

function setUnknownText(&$text) {
  $text = 'Sorry. I do not understand.';
}

function send($response, $response_text) {
  global $authorization;
  $response['text'] = $response_text;
  $json_string = json_encode($response);

  $slack_call = curl_init('https://slack.com/api/chat.postMessage');
  curl_setopt(
    $slack_call,
    CURLOPT_HTTPHEADER,
    array('Content-Type: application/json' , 'charset=utf-8', $authorization )
  );

  curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
  curl_setopt($slack_call, CURLOPT_CRLF, true);
  curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);


  $result = curl_exec($slack_call);
  $header_size = curl_getinfo($slack_call, CURLINFO_HEADER_SIZE);
  $header = substr($result, 0, $header_size);

  curl_close($slack_call);


  $body = substr($result, $header_size);
}

// Add handling of the app_mention event only
// This can be dangerous

if ($params) {


  $user_msg = $params['event']['text'];
  $user = $params['event']['user'];

  $conversation = ["user" => $user_msg];

  $response_text = '';
  $channel = $params['event']['channel'];

  $response = [
    "username" => "Smart Bot",
    "channel" => $channel,
  ];

  if ($params['event']['type'] === 'app_mention') {

    if(strExists($user_msg, 'hello')) {
      $response_text = 'hello!';
    }
    else if (strExists($user_msg, 'top trend')) {
      $conn = new mysqli($CONFIG['DB_SERVER'], $CONFIG['DB_ADMIN'], $CONFIG['DB_PASSWORD'], $CONFIG['DB_NAME']);

      $table = $CONFIG['DB_TABLE'];
      $stmt = $conn->prepare("SELECT `conversation` FROM `${table}` WHERE channel = ? AND user = ?");

      $stmt->bind_param("ss", $channel, $user);
      $stmt->execute();
      $stmt->bind_result($result);
      $stmt->fetch();
      $stmt->close();

      // json string
      // has responsed_trend
      if($result) {
        $responded_trend = json_decode($result, true);
      }
      else {
        $responded_trend = [];
      }

      $content = file_get_contents('data/mapping.json');

      $mapping = json_decode($content, true);

      $found = false;
      foreach ($mapping as $item) {
        if (!array_key_exists($item['text'], $responded_trend)) {
          $responded_trend[$item['text']] = 1;
          $found = $item;
          break;
        }
      }

      if ($found) {
        $json = json_encode($responded_trend);
        $stmt = $conn->prepare(
          "INSERT INTO `${table}` (`channel`, `user`, `conversation`) VALUES (?,?,?)
          ON DUPLICATE KEY UPDATE `conversation` = VALUES(`conversation`)"
        );
        $stmt->bind_param("sss", $channel, $user, $json);

        $stmt->execute();

        $csv = array_map('str_getcsv', file('data/' . $found['value']));

        $response_text = $found['text'] . "- ";
        for ($i = 1; $i <= 3; $i++) {
          $response_text .= $i . '. ';
          foreach ($csv[0] as $j => $col) {
            $response_text .= $col . ': ' . $csv[$i][$j] . ' ';
          }
        }

      }
      else {
        $response_text = 'Sorry I do not have any more new trends result at this moment';
      }

    }
    else {
      setUnknownText($response_text);
    }


  }

  send($response, $response_text);
}

// // WIDTH IS TOO SHORT - CAN'T USE INTERACTIVE MESSAGE
// // interactive_message
// else if ($slack_request) {

//   $params = urldecode($slack_request);
//   $json = substr($params, 8, strlen($params));
//   $params = json_decode($json, true);

//   $response = [
//     "username" => "Smart Bot",
//     "channel" => $params["channel"]["id"],
//   ];

//   if ($params['type'] === 'interactive_message') {
//     $response_text = 'here';
//   }
//   // other type
//   else {
//     setUnknownText($response_text);
//   }

//   send($response, $response_text);
// }
