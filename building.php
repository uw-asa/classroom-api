<?php

$json = isset($_GET['json']) && ($_GET['json'] || $_GET['json'] === '');
$inactive = isset($_GET['inactive']) && ($_GET['inactive'] || $_GET['inactive'] === '');

$args = array();
if (isset($_SERVER['PATH_INFO']))
  $args = explode( '/', $_SERVER['PATH_INFO'] );

if (count($args) > 1)
  $_GET['building'] = urldecode($args[1]);


include('../misc.php');

if ($_GET['building'] == 'EE1' || $_GET['building'] == 'EEB')
        $_GET['building'] = 'ECE';

require '../SpaceWS/vendor/autoload.php';
use UW\SpaceWS\Facility;
use UW\SpaceWS\Room;

include '../uw_ws.php';

#get building
try {
    /* Query the web services */
    $facility = Facility::fromFacilityCode($_GET['building']);
} catch (Exception $e) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    exit();
}

dprint_r($facility);

$results['building_code'] = $facility->FacilityCode;
$results['building_name'] = $facility->LongName;

#Get rooms:
$rooms = Room::search("facility_number={$facility->FacilityNumber}&room_type=110");

dprint_r($rooms);

if (count($rooms)) {
    foreach ($rooms as $roomObj) {

        $room = $roomObj->Facility->FacilityCode.' '.$roomObj->RoomNumber;
        $roomname = null; #$row['Name']

        if ( $json ) {

            $results['room_list'][$room]['building_code'] = $roomObj->Facility->FacilityCode;
            #$results['room_list'][$room]['room_name'] = $roomObj['Name'];
            $results['room_list'][$room]['room_number'] = $roomObj->RoomNumber;
            $results['room_list'][$room]['room_capacity'] = $roomObj->Capacity;
            $results['room_list'][$room]['room_type'] = ucfirst(strtolower($roomObj->Description));
            #$results['room_list'][$room]['room_notes'] = $row['Notes'];

        } else {

            if (! isset($currentFloor)) {
                $currentFloor = substr($roomObj->RoomNumber, 0, 1);
            }

            if (substr($roomObj->RoomNumber, 0, 1) != $currentFloor) {
                $currentFloor = substr($roomObj->RoomNumber, 0, 1);
            }
?>
  <option value="<?= $roomObj->RoomNumber ?>">
     <?= $room ?>
<?php   if ($roomname): ?>
     (<?= $roomname ?>)
<?php   endif; ?>
  </option>
<?php

        } # !$json

    }
} elseif (!$json) {
?>
 <h3>No room information available</h3>
<?php
}

if ($json)
{
  if ($facility && $facility->CenterPointLongitude != 0 && $facility->CenterPointLatitude != 0) {
    $results['access_url'] = sprintf("https://depts.washington.edu/ceogis/Public/Accessibility/Map/?marker=%f,%f,,%s&level=%d",
				     $facility->CenterPointLongitude, $facility->CenterPointLatitude, rawurlencode($results['building_name']), 3);
  }

  echo json_encode($results);

}

if (! isset($_GET['debug']))
    exit();

?>
<br clear="all" />
<hr />
<pre style="text-align: left; color: black; background-color: white">
<?= $debug_output ?>
</pre>
