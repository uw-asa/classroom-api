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

include('../misc.php');

include('../pdf.php');

include('r25.php');

$image_dir = "/var/www/room-images";

require '../SpaceWS/vendor/autoload.php';
use UW\SpaceWS\Facility;
use UW\SpaceWS\Room;

include '../uw_ws.php';

if (isset($_GET['room'])) {
    list($building, $number) = explode(' ', $_GET['room']);
    if ($building == 'EE1' || $building == 'EEB' || $building == 'ECE')
        $building = 'ECE';

} else {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    exit();
}    

try {
    /* Query the web services */
    $space = Room::fromFacilityCodeAndRoomNumber($building, $number);
} catch (Exception $e) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    exit();
}

dprint(print_r($space, true));


if (isset($_GET['room'])) {
    list($building, $number) = explode(' ', $_GET['room']);
    if ($building == 'EE1' || $building == 'EEB' || $building == 'ECE')
        $building = 'EEB';

    $short_name = $building . ' ' . $number;
} else {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    exit();
}    

$roomInfo25 = r25_get_space_by_short_name($short_name);
dprint(print_r($roomInfo25, true));

$nameInfo = r25_decode_formal_name((string)$roomInfo25->formal_name);

if (isset($_GET['debug'])) {
?>
<form method="POST">
 <input type="submit" name="update_cache" value="Reload Cached Entries" />
</form>
<?php
}

$building = $space->Facility->FacilityCode;
$number = $space->RoomNumber;

$results['building_code'] = $building;
$results['room_name'] = str_replace('Seattle- ', '', $nameInfo['name']);
$results['room_number'] = $number;
$results['room_capacity'] = $space->Capacity;
$results['room_type'] = ucfirst(strtolower($space->Description));


# New static locations for media files
$pdf = sprintf("%s_%s_schematic.pdf", $building, $number);
$png = sprintf("%s_%s_schematic_thumbnail.png", $building, $number);
if (empty($results['schematic_url']) &&
    is_file("$image_dir/schematics/$pdf") &&
    is_readable("$image_dir/schematics/$pdf"))
{
    $results['schematic_url'] = "http://www.cte.uw.edu/images/rooms/schematics/$pdf";
    $results['schematic_thumbnail_url'] = "//www.cte.uw.edu/images/rooms/schematics/$png";
}

$pdf = sprintf("%s_%s_instructions.pdf", $building, $number);
if (empty($results['instructions_url']) &&
    is_file("$image_dir/instructions/$pdf") &&
    is_readable("$image_dir/instructions/$pdf"))
{
    $results['instructions_url'] = "http://www.cte.uw.edu/images/rooms/instructions/$pdf";
}

$jpg = sprintf("%s_%s_panorama.jpg", $building, $number);
if (empty($results['panorama_url']) &&
    is_file("$image_dir/panoramas/$jpg") &&
    is_readable("$image_dir/panoramas/$jpg"))
{
    $results['panorama_url'] = "//www.cte.uw.edu/images/rooms/panoramas/$jpg";
}

if (isset($_GET['meta'])) {

  // Legacy meta

  switch($_GET['meta']) {
  case 'instructions':
    header("Location: //www.cte.uw.edu/images/rooms/instructions/{$building}_{$number}_instructions.pdf", TRUE, 301);
    exit();
  case 'schematic':
    header("Location: //www.cte.uw.edu/images/rooms/schematics/{$building}_{$number}_schematic.pdf", TRUE, 301);
    exit();
  case 'schematic_thumbnail':
    header("Location: //www.cte.uw.edu/images/rooms/schematics/{$building}_{$number}_schematic_thumbnail.png", TRUE, 301);
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






echo json_encode($results);

if (! isset($_GET['debug']))
    exit();

?>
<br clear="all" />
<hr />
<pre style="text-align: left; color: black; background-color: white">
<?= $debug_output ?>
</pre>
