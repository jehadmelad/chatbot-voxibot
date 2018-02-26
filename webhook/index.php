<?php

include_once 'include/tools.php';

/*
$params['name'] = 'momo';
$params['genre'] = 'madame';
//$params['test'] = '123';

database_set("0677379042", $params);

exit;
*/

//include 'calculator/index.php';
//exit;


$update_response = file_get_contents("php://input");
$update = json_decode($update_response, true);

debug("\n\n\n");
//debug(">>>> JSON : ".json_encode($update, JSON_PRETTY_PRINT));
debug("----");

// Session ID
if (isset($update["sessionId"]))
{
  $sessionid= $update['sessionId'];
  $sessionid = str_replace(".", "-", $sessionid);
  session_id($sessionid);
}
if (isset($update["session"]))
{
  $sessionid= $update['session'];
  $sessionid = str_replace(".", "-", $sessionid);
  session_id($sessionid);
}

if (session_start())
{
  debug("Server=".$_SERVER['HTTP_USER_AGENT']);
  if (isset($sessionid))
  debug("Start session=" . $sessionid);
}

debug("    session=" . json_encode($_SESSION));

if (isset($update["lang"])) {
  $_SESSION['lang'] = $update["lang"];
  debug("    lang=" . $_SESSION['lang']);
}

// Store the query
if (isset($update["result"]["resolvedQuery"])) {
  $GLOBALS["query"] = $update["result"]["resolvedQuery"];
  debug("    query=" . $GLOBALS["query"]);
}

// Store parameters
if (isset($update["result"]["parameters"])) {
  $GLOBALS["parameters"] = $update["result"]["parameters"];

  foreach ($GLOBALS["parameters"] as $key => $value) {
    //debug("{$key} => {$value} ");

    $GLOBALS["parameters"][$key] = utf8_decode(template($GLOBALS["parameters"][$key]));

    /*
    if ($value[0]=='%')
    {
      $var = explode('.', $value);

      if (substr($var[1], -1)=='%')
      $var[1] = substr($var[1], 0, -1);

      if ($var[0]=="%session")
      $GLOBALS["parameters"][$key]=$_SESSION[$var[1]];
      if ($var[0]=="%user")
      $GLOBALS["parameters"][$key]=$_SESSION["user"][$var[1]];
    }
    */
  }

  debug("    parameters=".json_encode($GLOBALS['parameters']));
}

// Store the text
if (isset($update["result"]["fulfillment"]["speech"])) {
  $GLOBALS["speech"] = $update["result"]["fulfillment"]["speech"];
  debug("    speech=".$GLOBALS["speech"]);
}

if (isset($update["result"]["action"]))
if ($update["result"]["action"] != '')
{
  $GLOBALS["action"] = $update["result"]["action"];
  debug("    action=".$GLOBALS["action"]);
}

if (isset($update["result"]["metadata"]))
if (isset($update["result"]["metadata"]["intentName"]))
{
  $GLOBALS["intent"] = $update["result"]["metadata"]["intentName"];
  debug("    intent=".$GLOBALS["intent"]);
}

if (!isset($_SESSION["user"]))
if (isset($update["originalRequest"]["source"]))
{
  // google
  if ($update["originalRequest"]["source"] == "google")
  if (isset($update["originalRequest"]["data"]["user"])) {
    $_SESSION["lastSeen"]= $update["originalRequest"]["data"]["user"]["lastSeen"];
    $_SESSION["locale"]= $update["originalRequest"]["data"]["user"]["locale"];
    $_SESSION["userId"] = $update["originalRequest"]["data"]["user"]["userId"];
    $_SESSION["caller"] = 'google';
    $_SESSION["called"] = 'google/voxibot';
    $_SESSION["user"] = database_get($_SESSION["userId"], "google");
    debug("    user(google)=".json_encode($_SESSION["user"], JSON_PRETTY_PRINT));
  }

  // voxibot
  if ($update["originalRequest"]["source"] == "voxibot")
  if (isset($update["originalRequest"]["data"]["user"])) {
    $_SESSION["param"]= $update["originalRequest"]["data"]["user"]["param"];
    $_SESSION["called"]= $update["originalRequest"]["data"]["user"]["called"];
    $_SESSION["caller"] = $update["originalRequest"]["data"]["user"]["caller"];
    $_SESSION["user"] = database_get($_SESSION["caller"], "voxibot");
    debug("    user(voxibot)=".json_encode($_SESSION["user"], JSON_PRETTY_PRINT));
  }
}

// For Facebook
if (false)
{
  $source = "https://myapps.gia.edu/ReportCheckPortal/downloadReport.do?reportNo=1152872617&weight=1.35";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $source);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSLVERSION,3);
  $data = curl_exec ($ch);
  $error = curl_error($ch);
  curl_close ($ch);

  $destination = "/tmp/test.wav";
  $file = fopen($destination, "w+");
  fputs($file, $data);
  fclose($file);
}

//$GLOBALS['nossml'] = true;

// Voxibot Actions
if (isset($_SESSION['echo']))
{
  $GLOBALS["output"] = $GLOBALS["query"];
}
else
if (isset($_SESSION['message']))
{
  debug("    message=".$GLOBALS["query"]);

  if (isset($GLOBALS["parameters"]["phone-number"]))
  $_SESSION["message_destination"] = $GLOBALS["parameters"]["phone-number"];
  $_SESSION["message_text"] = $GLOBALS["query"];
  unset($_SESSION["message"]);
  unset($GLOBALS["action"]);
  //$GLOBALS['result']['context'] = "Message-followup";
  if ($GLOBALS["intent"]!="Message - Record")
  $GLOBALS['result']['event'] = "message";
}
else
if (isset($_SESSION['agent']))
{

}


if (isset($GLOBALS['action']))
{
  $action = strtolower($GLOBALS['action']);
  $GLOBALS['action'] = $action;

  $actions = explode(';', $action);

  foreach($actions as $action)
  {
    debug("    action(execute)=".$action);

    switch ($action) {
      case 'input.welcome':
          {
            if (isset($update["result"]["parameters"]["phone"]))
            $_SESSION["phone"] = $update["result"]["parameters"]["phone"];
            if (isset($update["result"]["parameters"]["voice"]))
            $_SESSION["voice"] = $update["result"]["parameters"]["voice"];
          }
          break;
      case 'database.get':
          {
            $phone = get_parameter('phone');
            database_get($phone);
          }
          break;
      case 'database.set':
          {
            $phone = "0677379042";
            //$phone = get_parameter('phone');
            database_set($phone, $GLOBALS["parameters"]);
          }
          break;
      case 'voice':
          {
            if (isset($update["result"]["parameters"]["voice"]))
            $_SESSION["voice"] = $update["result"]["parameters"]["voice"];
            debug("    voice=".$_SESSION["voice"]);
          }
          break;
      case 'hangup':
          {
            $GLOBALS['hangup'] = true;
            debug("    hangup...");
          }
          break;
      case 'echo':
          {
            $_SESSION["echo"] = true;
            debug("    echo mode...");
          }
          break;
      case 'message':
          {
            $_SESSION["message"] = true;
            debug("    wait for a message...");
          }
          break;
      case 'data':
          {
            $_SESSION["data"] = database_get($GLOBALS["parameters"]["value"], "voxibot");
            debug("    data=".json_encode($_SESSION["data"]));
          }
          break;
      default:
          {
            $action_elements = explode('.', $action);

            if (isset( $action_elements[1]))
            $GLOBALS["action_file"] = $action_elements[1];
            if (isset( $action_elements[2]))
            $GLOBALS['action_function'] = $action_elements[2];

            if ($action_elements[0] == "execute")
            if (file_exists($GLOBALS["action_file"]."/index.php"))
            {
              include $GLOBALS["action_file"]."/index.php";
              debug("Back to main file.");
            }
          }
    }
  }
}


//$_SESSION["data"]["name"] = "Borja";

if (isset($_SESSION["voice"]))
$GLOBALS["voice"]  = $_SESSION["voice"];


debug("    session=" . json_encode($_SESSION));
//debug("Main : Context before processMessage GLOBAL:" . json_encode($GLOBALS));

if (isset($GLOBALS["output"]))
$GLOBALS["speech"] = template($GLOBALS["output"]);
else
$GLOBALS["speech"] = template($GLOBALS["speech"]);

debug("    speech(output)=".$GLOBALS["speech"]);


// Get Action
$message_answer = processMessage($update);

debug("----");
//debug("<<<< JSON : ".json_encode($message_answer, JSON_PRETTY_PRINT));

sendMessage($message_answer);


?>
