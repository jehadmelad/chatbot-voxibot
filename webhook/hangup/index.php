<?php

@include_once '../include/tools.php';
$direct = false;

// Get parameters
{
  $operation = get_parameter("operation");
  $number1 = get_parameter("number1");
  $number2 = get_parameter("number2");

  LogToApache("Calculator : " . json_encode($GLOBALS['parameters']));
  LogToApache("Calculator : " .$number1. ' '.$operation.' '.$number2);
}

// Processing
{
  LogToApache("Hangup : no processing");
}

// Set results
{
  $GLOBALS['result']['hangup'] = true;
  $GLOBALS['result']['code'] = 'OK';

  if ($direct)
  echo json_encode($GLOBALS['result']);
  //else
  //$GLOBALS["output"] = $GLOBALS['result']['text'];

  LogToApache("Calculator : " . $GLOBALS["output"]);
}


?>