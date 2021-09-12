<?php

$starttime = microtime(true);

$args = array();
if (isset($_SERVER['PATH_INFO']))
  $args = explode( '/', $_SERVER['PATH_INFO'] );

if (count($args) > 2) {
  $_GET['meta'] = urldecode($args[2]);
}

if (count($args) > 1) {
  $_GET['room'] = urldecode($args[1]);
}

include('misc.php');

include('r25.php');

$image_dir = "../room-images";
$image_url = "http://{$_SERVER['SERVER_NAME']}/room-images";

require 'vendor/autoload.php';
use UW\SpaceWS\Facility;
use UW\SpaceWS\Room;

include '/usr/local/etc/uw_ws/config.php';

if (isset($_GET['room'])) {
    list($building, $number) = explode(' ', $_GET['room']);
} else {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    exit();
}    

if ($building == 'EE1' || $building == 'EEB' || $building == 'ECE')
    $building = 'ECE';

try {
    /* Query the web services */
    $space = Room::fromFacilityCodeAndRoomNumber($building, $number);
} catch (Exception $e) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    exit();
}

dprint(print_r($space, true));

$short_name = $building . ' ' . $number;
$roomInfo25 = r25_get_space_by_short_name($short_name);
dprint(print_r($roomInfo25, true));

$nameInfo = r25_decode_formal_name((string)$roomInfo25->formal_name);

$building = $space->Facility->FacilityCode;
$number = $space->RoomNumber;

$results['building_code'] = $building;
$results['room_name'] = $nameInfo['name'];
$results['room_number'] = $number;
$results['room_capacity'] = $space->Capacity;
$results['room_type'] = ucfirst(strtolower($space->Description));
$results['room_notes'] = '';

$number = r25_canonicalize_room_number($number);

# New static locations for media files
$pdf = sprintf("%s_%s_schematic.pdf", $building, $number);
$png = sprintf("%s_%s_schematic_thumbnail.png", $building, $number);
if (empty($results['schematic_url']) &&
    is_file("$image_dir/schematics/$pdf") &&
    is_readable("$image_dir/schematics/$pdf"))
{
    $results['schematic_url'] = "{$image_url}/schematics/$pdf";
    $results['schematic_thumbnail_url'] = "{$image_url}/schematics/$png";
}

$jpg = sprintf("%s_%s_panorama.jpg", $building, $number);
if (empty($results['panorama_url']) &&
    is_file("$image_dir/panoramas/$jpg") &&
    is_readable("$image_dir/panoramas/$jpg"))
{
    $results['panorama_url'] = "{$image_url}/panoramas/$jpg";
}

if (isset($_GET['meta'])) {

  // Legacy meta

  switch($_GET['meta']) {
  case 'instructions':
    header("Location: {$image_url}/instructions/{$building}_{$number}_instructions.pdf", TRUE, 301);
    exit();
  case 'schematic':
    header("Location: {$image_url}/schematics/{$building}_{$number}_schematic.pdf", TRUE, 301);
    exit();
  case 'schematic_thumbnail':
    header("Location: {$image_url}/schematics/{$building}_{$number}_schematic_thumbnail.png", TRUE, 301);
    exit();
  }


  // unhandled meta
  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");

  include('error/404.php');

  exit();
}


foreach($roomInfo25->feature as $featureObj) {
    $feature = array_merge((array)$featureObj, r25_decode_feature_name((string)$featureObj->feature_name));


    $attribute = array(
                       'name' => $feature['display_name'],
                       'quantity' => $feature['quantity'],
#                       'length' => $row['Length'],
#                       'width' => $row['Width'],
#                       'notes' => $row['Notes'],
		       );

    $results['attribute_list'][$feature['category']][] = $attribute;
}

ksort($results['attribute_list']);

foreach($roomInfo25->custom_attribute as $row) {
    $attribute = array(
                       'name' => (string)$row->attribute_name,
                       'quantity' => 1,
#                       'length' => $row['Length'],
#                       'width' => $row['Width'],
                       'notes' => (string)$row->attribute_value,
		       );

#    $results['attribute_list']['Custom Attributes'][] = $attribute;
}

$instructions_pdf = sprintf("%s_%s_instructions.pdf", $results['building_code'], $results['room_number']);
if (is_file("$image_dir/instructions/$instructions_pdf") &&
    is_readable("$image_dir/instructions/$instructions_pdf"))
{
    $results['service_urls']['Room instructions'] = "{$image_url}/instructions/$instructions_pdf";
}

$results['service_urls']['Report a problem'] = "https://www.cte.uw.edu/academictechnologies/room-problem/?building={$results['building_code']}&room_number={$results['room_number']}";

switch ("{$results['building_code']} {$results['room_number']}") {
case 'MGH 030':
case 'MGH 044':
    $results['service_urls']['Computer specifications'] = 'https://itconnect.uw.edu/learn/technology-spaces/mgh-044-computer-classroom/mgh-030-and-mgh-044-software-and-hardware-list/';
    break;
default:
    break;
}

switch ($results['room_type']) {
case 'Active Learning Classroom':
case 'Auditorium':
case 'Breakout Room':
case 'Case Study Classroom':
case 'Computer Classroom':
case 'Classroom':
case 'Seminar Room':
    $results['service_urls']['Emergency procedures'] = 'https://www.ehs.washington.edu/system/files/resources/classroom-evacuations.pdf';
    break;
default:
    break;
}

if (! isset($_GET['debug']))
    exit(json_encode($results));

?>
<form method="POST"><input type="submit" name="update_cache" value="Reload Cached Entries" /></form>
<pre><?= htmlentities(json_encode($results, JSON_PRETTY_PRINT)) ?></pre>
<hr />
<pre><?= $debug_output ?></pre>