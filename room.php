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
$image_url = "https://{$_SERVER['SERVER_NAME']}/room-images";

if (isset($_GET['room'])) {
    list($building, $number) = explode(' ', $_GET['room']);
} else {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    goto RoomExit;
}    

if ($building == 'EE1' || $building == 'EEB' || $building == 'ECE')
    $building = 'ECE';

$short_name = $building . ' ' . $number;
$roomInfo25 = r25_get_space_by_short_name($short_name);
dprint(print_r($roomInfo25, true));

if (!is_numeric($roomInfo25->space_id)) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    if (! isset($_GET['debug']))
        goto RoomExit;
}

$nameInfo = r25_decode_formal_name((string)$roomInfo25->formal_name);

$results['building_code'] = $building;
$results['room_name'] = $nameInfo['name'];
$results['room_number'] = $number;
// $results['room_capacity'] = $space->Capacity;
$results['price_group'] = '';
$results['room_notes'] = '';

#$number = r25_canonicalize_room_number($number);

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
  if (! isset($_GET['debug']))
      goto RoomExit;
}


foreach($roomInfo25->feature as $featureObj) {
    $feature = array_merge((array)$featureObj, r25_decode_feature_name((string)$featureObj->feature_name));
    if ($feature['category'] == 'Hidden')
        continue;

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

foreach (array_keys($results['attribute_list']) as $category) {
    usort($results['attribute_list'][$category], function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}

switch ($results['building_code']) {
case 'MGH':
case 'SMI':
    $results['service_urls']['Calendar*'] = "https://25live.collegenet.com/pro/washington#!/home/location/{$roomInfo25->space_id}/calendar";
    break;
}

$instructions_pdf = sprintf("%s_%s_instructions.pdf", $results['building_code'], $results['room_number']);
if (is_file("$image_dir/instructions/$instructions_pdf") &&
    is_readable("$image_dir/instructions/$instructions_pdf"))
{
    $results['service_urls']['Room instructions'] = "{$image_url}/instructions/$instructions_pdf";
}

$results['service_urls']['Report a problem'] = "https://academictechnologies.asa.uw.edu/room-problem/?building={$results['building_code']}&room_number={$results['room_number']}";

switch ("{$results['building_code']} {$results['room_number']}") {
case 'FTR 034':
    // Not a generally-assignable room
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    if (! isset($_GET['debug']))
        goto RoomExit;
case 'MGH 030':
case 'MGH 044':
    $results['service_urls']['Computer specifications'] = 'https://itconnect.uw.edu/learn/technology-spaces/mgh-044-computer-classroom/mgh-030-and-mgh-044-software-and-hardware-list/';
    break;
case 'PAR 220':
    // not reservable through Event Services
    $results['service_urls']['Special event request'] = null;
default:
    break;
}

// FIXME: most rooms have no Room Type feature in R25
switch ($results['attribute_list']['Room Type'][0]['name']) {
case 'Active Learning Classroom':
case 'Computer Lab -- Pc/Windows':
case 'Film Room':
// above three are only ones in R25
case 'Auditorium':
case 'Breakout Room':
case 'Case Study Classroom':
case 'Computer Classroom':
case 'Classroom':
case 'Seminar Room':
    $results['service_urls']['Emergency procedures'] = 'https://www.ehs.washington.edu/system/files/resources/classroom-evacuations.pdf';
    break;
default:
    $results['service_urls']['Emergency procedures'] = 'https://www.ehs.washington.edu/system/files/resources/classroom-evacuations.pdf';
    break;
}

RoomExit:
if (! isset($_GET['debug']))
    exit(json_encode($results));

?>
<form method="POST"><input type="submit" name="update_cache" value="Reload Cached Entries" /></form>
<pre><?= htmlentities(json_encode($results, JSON_PRETTY_PRINT)) ?></pre>
<hr />
<pre><?= $debug_output ?></pre>
