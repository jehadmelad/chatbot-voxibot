<?php

$direct = @include_once '../include/tools.php';

define("EMAIL_ADDRESS", "youlichika@hotmail.com");


/*
$ch = curl_init();
$cv = curl_version();
$user_agent = "curl ${cv['version']} (${cv['host']}) libcurl/${cv['version']} ${cv['ssl_version']} zlib/${cv['libz_version']} <" . EMAIL_ADDRESS . ">";
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt");
curl_setopt($ch, CURLOPT_ENCODING, "deflate, gzip, identity");
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
curl_setopt($ch, CURLOPT_URL, "http://en.wikipedia.org/w/api.php?action=query&generator=allpages&gaplimit=2&gapfilterredir=nonredirects&gapfrom=Re&prop=revisions&rvprop=content&format=xml");
$xml = curl_exec($ch);
$xml_reader = new XMLReader();
$xml_reader->xml($xml, "UTF-8");

function extract_first_rev(XMLReader $xml_reader)
{
    while ($xml_reader->read()) {
        if ($xml_reader->nodeType == XMLReader::ELEMENT) {
            if ($xml_reader->name == "rev") {
                $content = htmlspecialchars_decode($xml_reader->readInnerXML(), ENT_QUOTES);
                return $content;
            }
        } else if ($xml_reader->nodeType == XMLReader::END_ELEMENT) {
            if ($xml_reader->name == "page") {
                throw new Exception("Unexpectedly found `</page>`");
            }
        }
    }

    throw new Exception("Reached the end of the XML document without finding revision content");
}

$latest_rev = array();
while ($xml_reader->read()) {
    if ($xml_reader->nodeType == XMLReader::ELEMENT) {
        if ($xml_reader->name == "page") {
            $latest_rev[$xml_reader->getAttribute("title")] = extract_first_rev($xml_reader);
        }
    }
}

function parse($rev)
{
    global $ch;

    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
    curl_setopt($ch, CURLOPT_URL, "http://en.wikipedia.org/w/api.php?action=parse&text=" . rawurlencode($rev) . "&prop=text&format=xml");
    sleep(3);
    $xml = curl_exec($ch);
    $xml_reader = new XMLReader();
    $xml_reader->xml($xml, "UTF-8");

    while ($xml_reader->read()) {
        if ($xml_reader->nodeType == XMLReader::ELEMENT) {
            if ($xml_reader->name == "text") {
                $html = htmlspecialchars_decode($xml_reader->readInnerXML(), ENT_QUOTES);
                return $html;
            }
        }
    }

    throw new Exception("Failed to parse");
}

foreach ($latest_rev as $title => $latest_rev) {
    echo parse($latest_rev) . "\n";
}

exit;
*/


$json = file_get_contents('php://input');
$request = json_decode($json, true);
$action = $request["result"]["action"];
$parameters = $request["result"]["parameters"];
$metadata = $request["result"]["metadata"];

//[Code to set $outputtext, $nextcontext, $param1, $param2 values]

$outputtext = "from PHP " . $_REQUEST['search'];

//$parameters['any'] = "france";
//$metadata['intentName'] = 'wikipedia';


function makeCall($url, $options) {
    $curl = curl_init();
    $cv = curl_version();
    $user_agent = "curl ${cv['version']} (${cv['host']}) libcurl/${cv['version']} ${cv['ssl_version']} zlib/${cv['libz_version']} <" . EMAIL_ADDRESS . ">";
    curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "cookies.txt");
    curl_setopt($curl, CURLOPT_COOKIEJAR, "cookies.txt");
    curl_setopt($curl, CURLOPT_ENCODING, "deflate, gzip, identity");
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
    curl_setopt($curl, CURLOPT_URL, $url);

    return curl_exec($curl);
}

function wiki($search)
{
  $wikiURL = 'http://fr.wikipedia.org/w/api.php?action=opensearch&format=xml&limit=1&search=' . urlencode($search);

  $options = array(
		CURLOPT_HTTPGET => TRUE,
		CURLOPT_POST => FALSE,
		CURLOPT_HEADER => false,
		CURLOPT_NOBODY => FALSE,
		CURLOPT_VERBOSE => FALSE,
		CURLOPT_REFERER => "",
		CURLOPT_USERAGENT => "Voximal",
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_FOLLOWLOCATION => TRUE,
		CURLOPT_MAXREDIRS => 4
  );
  $wikiText = makeCall($wikiURL, $options);
  #save_file(_DEBUG_PATH_ . 'wiki_return.txt', $wikiText);
  $xml = simplexml_load_string($wikiText, 'SimpleXMLElement', LIBXML_NOCDATA);
  if((string)$xml->Section->Item->Description)
  {
    $description = (string)$xml->Section->Item->Description;
    $image = (string)$xml->Section->Item->Image->asXML();
    $image = str_replace('<Image source', '<img src', $image);
    $linkHref = (string)$xml->Section->Item->Url;
    $linkText = (string)$xml->Section->Item->Text;
    $link = "<a href=\"$linkHref\" target=\"_blank\">$linkText</a>";
    //$output = "$link<br/>\n$image<br/>\n$description";
    $output = $description;
  }
  else $output = 'Wikipedia returned no results 2!'.$wikiText;
  //$output = '<![CDATA[' . $output . ']]>'; // Uncomment this line when using the XHTML doctype
  return $output;
}


// Get parameters
{
  $search = get_parameter("search");
}

// Processing
{
  $output = wiki($search);
}

// Set results
{


  $GLOBALS['result']['input'] = $search;
  $GLOBALS['result']['text'] = $output;
  $GLOBALS['result']['code'] = 'OK';
  //$GLOBALS['result']['event'] = "GOODBYE";

  debug("Wikipedia : " . json_encode($GLOBALS['result']));

  if (is_json())
  {
    sendMessage($GLOBALS['result']);
  }
  else
  $GLOBALS["output"] = $GLOBALS['result']['text'];
}
?>