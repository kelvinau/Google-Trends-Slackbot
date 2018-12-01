<?php
function logToFile($msg) {
  file_put_contents('slack-post.log', $msg.PHP_EOL , FILE_APPEND | LOCK_EX);
}
$slack_request = file_get_contents('php://input');
$params = json_decode($slack_request, true);

logToFile($slack_request);

// Add handling of the app_mention event only
if ($params['event']['type'] === 'app_mention') {

  function strExists($string, $search) {
    return strpos( strtolower($string), $search ) !== false;
  }

  $CONFIG = parse_ini_file("config.ini");

  $authorization = "Authorization: Bearer " . $CONFIG['BOT_TOKEN'];
  $user_msg = $params['event']['text'];
  $user = $params['event']['user'];

  $conversation = ["user" => $user_msg];

  $response_text = '';
  $channel = $params['event']['channel'];

  $response = [
    "username" => "Smart Bot",
    "channel" => $channel,
  ];

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
      if (strExists($user_msg, 'interesting')) {
        $response_text = 'I have a list of interesting items. Which one do you want to know?';
        $conversation["bot"] = $response_text;
        $stmt = $conn->prepare("INSERT INTO `${table}` (`channel`, `user`, `conversation`) VALUES (?,?,?)");

        $stmt->bind_param("sss", $channel, $user, json_encode($conversation));
        // TODO: REMOVE THIS
        false && $stmt->execute();

        // select options
        $response = array_merge($response, [
            "response_type" => "in_channel",
            "attachments" => [
                [
                    "text" => "Choose a game to play",
                    "fallback" => "If you could read this message, you'd be choosing something fun to do right now.",
                    "color" => "#3AA3E3",
                    "attachment_type" => "default",
                    "callback_id" => "game_selection",
                    "actions" => [
                        [
                            "name" => "games_list",
                            "text" => "Pick a game...",
                            "type" => "select",
                            "options" =>[
                                [
                                    "text" => "Hearts",
                                    "value" => "hearts"
                                ],
                                [
                                    "text" => "Bridge",
                                    "value" => "bridge"
                                ],
                            ]
                        ]
                    ]
                ]
              ]
            ]
          );



      }
      else {
        $response_text = 'Sorry. I do not understand.';
      }
    }






  }

  $response["text"] = $response_text;




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
