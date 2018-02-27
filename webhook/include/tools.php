<?php

function is_json()
{
  return strncmp($_SERVER['HTTP_USER_AGENT'], 'Apache-HttpClient', 17);
}

function get_parameter($name, $default='?')
{
  if (isset($_REQUEST[$name]))
  return $_REQUEST[$name];

  if (isset($GLOBALS['parameters'][$name]))
  return $GLOBALS['parameters'][$name];

  return $default;
}


function debug($Message) {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr,$Message."\n");
        fclose($stderr);
}


function template($string) {

  $string = preg_replace_callback('/\%data.(\\S+)\%/'
    ,create_function('$matches', 'return @$_SESSION["data"][$matches[1]];') ,$string);

  $string = preg_replace_callback('/\%user.(\\S+)\%/'
    ,create_function('$matches', 'return @$_SESSION["user"][$matches[1]];') ,$string);

  $string = preg_replace_callback('/\%parameter.(\\S+)\%/'
    ,create_function('$matches', 'return @$GLOBALS["parameters"][$matches[1]];') ,$string);

  $string = preg_replace_callback('/\%result.(\\S+)\%/'
    ,create_function('$matches', 'return @$GLOBALS["result"][$matches[1]];') ,$string);

  $string = preg_replace_callback('/\%session.(\\S+)\%/'
    ,create_function('$matches', 'return @$_SESSION[$matches[1]];') ,$string);

  $string = preg_replace_callback('/\%(\\S+)\%/'
    ,create_function('$matches', 'return @$GLOBALS[$matches[1]];') ,$string);

  return $string;
}


function database_get($id = "", $key = "voxibot") {
  $link = mysql_connect('localhost', 'root', '') or die('Impossible de se connecter : ' . mysql_error());
  mysql_select_db('voxibot') or die('Impossible de sélectionner la base de données');

  debug("GET database ". json_encode($_SESSION));

  if (($id != "") && ($key == "voxibot"))
  $query = 'SELECT * FROM users WHERE userPhone = \''.$id.'\'';
  else
  if (($id != "") && ($key == "google"))
  $query = 'SELECT * FROM users WHERE userId = \''.$id.'\'';
  else
  if (isset($_SESSION['caller']))
  $query = 'SELECT * FROM users WHERE userPhone = \''.$_SESSION['userPhone'].'\'';
  else
  if (isset($_SESSION['userId']))
  $query = 'SELECT * FROM users WHERE userId = \''.$_SESSION['userId'].'\'';
  else
  return;

  debug("GET database query ". $query);


  $result = mysql_query($query) or die('Échec de la requête : ' . mysql_error());

  if ($result)
  {
    $data = mysql_fetch_array($result, MYSQL_ASSOC);
  }

  mysql_free_result($result);
  mysql_close($link);

  return $data;
}

function database_set($phone, $parameters) {
  $link = mysql_connect('localhost', 'root', '') or die('Impossible de se connecter : ' . mysql_error());
  mysql_select_db('voxibot') or die('Impossible de sélectionner la base de données');

  $more = '';
  $query = "UPDATE users SET ";
  foreach($parameters as $k => $v) {
    $query .= $more." `".$k."`='".$v."'";
    $more = ',';
  }
  $query .= " WHERE userPhone='".$phone."'";

  debug("database set : " . $query);


  $result = @mysql_query($query) or die('Échec de la requête : ' . mysql_error());

  mysql_close($link);

  return;
}

function processMessage($update) {

  $message = array(
    "speech" => $GLOBALS['speech'],
    "displayText" => $GLOBALS['speech'],

/*
    "contextOut" => array(
      array(
        "name" => "momo",
        "parameters" => array(
          "action" => "action",
          "result" => "ok",
          "param1" => "momo",
          "param2" => "toto"
        ),
        "lifespan" => 10
     ),
    ),
*/

    "data" => array(),

/*
    "data" => array(
      "telegram" => array(
        "text" => "Telegrame ans",
        "parse_mode" => "Markdown",
        )
    ),
*/

  );

  if (isset($GLOBALS["voice"]))
  $message["data"]["google"] = array(
        "expect_user_response" => !isset($GLOBALS['hangup']),
        "is_ssml" => true, //!isset($GLOBALS['nossml']),
        "permissions_request" => array(
          "opt_context" => "opt_context",
        ),
        "richResponse" => array(
          "items" => array(
            array(
              "simpleResponse" => array(
                "textToSpeech" => $GLOBALS['speech'],
                "displayText" => $GLOBALS['speech'],
                "ssml" => "<speak><audio src=\"https://d1y0sibbj09q2p.cloudfront.net/tts.php?voice=".$GLOBALS["voice"]."&amp;text=".$GLOBALS['speech']."\"/></speak>",
                ),
            ),
/*
            array(
              "simpleResponse" => array(
                "textToSpeech" => "what else?",
                "displayText" => "what else?",
                "ssml" => "<speak><audio src=\"https://d1y0sibbj09q2p.cloudfront.net/tts.php?voice=".$GLOBALS["voice"]."&amp;text="."what else?"."\"/></speak>",
                ),
            ),
*/
          ),
        ),
      );

  if (isset($update["result"]["source"]))
  $message["source"] = $update["result"]["source"];

  if (isset($GLOBALS['result']['event']))
  {
    debug("Format MESSAGE Add event ".$GLOBALS['result']['event']);

    $message["followupEvent"] = array(
     "name" => $GLOBALS['result']['event'],
     "data" => array(
       "param1" => "1",
       "param2" => "2",
       "message" => $_SESSION['message_text'],
       ),
    );
  }

  if (isset($GLOBALS['result']['context']))
  {
    debug("Format MESSAGE Add context ".$GLOBALS['result']['context']);

    $message["contextOut"] = array(
     "name" => $GLOBALS['result']['context'],
     "data" => array(
       "param1" => "1",
       "param2" => "2",
       "message" => $_SESSION['message_text'],
       ),
      "lifespan" => 5,
    );
  }

  return $message;
}


function sendMessage($parameters) {
  header('Content-Type: application/json');
  echo json_encode($parameters);
}

function dialogflow($query)
{
  $postData = array('query' => $query, 'lang' => 'fr', 'sessionId' => $GLOBALS['sessionId']);
  $jsonData = json_encode($postData);

  $v = date('Ymd');
  $ch = curl_init('https://api.dialogflow.com/v1/query?v='.$v);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer b091f8442ab64407ad6a9bd6c4d45c2c'));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);

  $answerData = json_decode($result);

  return $answerData->result->fulfillment->speech;
}

?>
