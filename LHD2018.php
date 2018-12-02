<?php
$CONFIG = parse_ini_file("config.ini");

$authorization = "Authorization: Bearer " . $CONFIG['BOT_TOKEN'];


function logToFile($msg) {
  file_put_contents('slack-post.log', $msg.PHP_EOL , FILE_APPEND | LOCK_EX);
}
$slack_request = file_get_contents('php://input');
$params = json_decode($slack_request, true);

//logToFile($slack_request);

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
    else {
      $conn = new mysqli($CONFIG['DB_SERVER'], $CONFIG['DB_ADMIN'], $CONFIG['DB_PASSWORD'], $CONFIG['DB_NAME']);

      $table = $CONFIG['DB_TABLE'];
      $stmt = $conn->prepare("SELECT `conversation` FROM `${table}` WHERE channel = ? AND user = ?");

      $stmt->bind_param("ss", $channel, $user);
      $stmt->execute();
      $stmt->bind_result($result);
      $stmt->fetch();
      $stmt->close();

      // json string
      // has convo
      // TODO: REMOVE THIS
      if(false && $result) {
        $response_text = 'has convo';
      }
      // no convo
      else {
        if (strExists($user_msg, 'top trend')) {
          $response_text = 'I have a list of top trending items from Google search. Which one do you want to know?';
          $conversation["bot"] = $response_text;
          $stmt = $conn->prepare("INSERT INTO `${table}` (`channel`, `user`, `conversation`) VALUES (?,?,?)");

          $json = json_encode($conversation);
          $stmt->bind_param("sss", $channel, $user, $json);
          // TODO: REMOVE THIS
          false && $stmt->execute();

          // select options
          $content = file_get_contents('data/mapping.json');

          $mapping = json_decode($content, true);

          $response = array_merge($response, [
              "response_type" => "in_channel",
              "attachments" => [
                  [
                      "text" => "Choose an item",
                      "fallback" => "If you could read this message, you'd be choosing something fun to read right now.",
                      "color" => "#3AA3E3",
                      "attachment_type" => "default",
                      "callback_id" => "trend_selection",
                      "actions" => [
                          [
                              "name" => "games_list",
                              "text" => "Pick an item...",
                              "type" => "select",
                              "options" => $mapping,
                          ]
                      ]
                  ]
                ]
              ]
            );



        }
        else {
          setUnknownText($response_text);
        }
      }
    }
  }

  send($response, $response_text);
}
// interactive_message
else if ($slack_request) {





  $params = urldecode($slack_request);
  $json = substr($params, 8, strlen($params));
  $params = json_decode($json, true);

  $response = [
    "username" => "Smart Bot",
    "channel" => $params["channel"]["id"],
  ];

  if ($params['type'] === 'interactive_message') {
    $response_text = 'here';
  }
  // other type
  else {
    setUnknownText($response_text);
  }











  // $response = array(
      // "mrkdwn" => true,
      // "icon_url" => $icon_url,
      // "attachments" => array(
      //      array(
      //         "color" => "#b0c4de",
      //     //  "title" => $message_primary_title,
      //         "fallback" => $message_attachment_text,
      //         "text" => $message_attachment_text,
      //         "mrkdwn_in" => array(
      //             "fallback",
      //             "text"
      //         ),
      //         "fields" => array(
      //             array(
      //                 "title" => $message_other_options_title,
      //                 "value" => $message_other_options
      //             )
      //         )
      //     )
      // )
  // );




  send($response, $response_text);
}
