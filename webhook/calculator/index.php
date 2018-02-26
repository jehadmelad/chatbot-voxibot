<?php

@include_once '../include/tools.php';

// Get parameters
{
  if (isset($GLOBALS['parameters']))
  debug("Calculator : " . json_encode($GLOBALS['parameters']));

  $operation = get_parameter("operation");
  $number1 = get_parameter("number1");
  $number2 = get_parameter("number2");

  debug("Calculator : " .$number1. ' '.$operation.' '.$number2);
}

// Processing
{
  if ($operation == "+")
  {
    $value = ($number1 + $number2);
    $outputtext = $number1 . ' plus ' . $number2 . ' cela fait, ' . $value;
  }

  if ($operation == "-")
  {
    $value = ($number1 - $number2);
    $outputtext = $number1 . ' moins ' . $number2 . ' cela fait, ' . $value;
  }

  if ($operation == "*")
  {
    $value = ($number1 * $number2);
    $outputtext = $number1 . ' multiplié par ' . $number2 . ' cela fait, ' . $value;
  }

  if ($operation == "/")
  {
    $value = ($number1 / $number2);
    $outputtext = $number1 . ' divisé par ' . $number2 . ' cela fait, ' . $value;
  }

  debug("Calculator : " . $outputtext);
}

// Set results
{
  $GLOBALS['result']['speech'] = $outputtext;
  $GLOBALS['result']['text'] = $outputtext;
  $GLOBALS['result']['code'] = 'OK';
  $GLOBALS['result']['value'] = $value;

  debug("Calculator : " . json_encode($GLOBALS['result']));

  if (is_json())
  {
    sendMessage($GLOBALS['result']);
  }

  //debug("Calculator : " . $GLOBALS["output"]);
}


?>