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

# get building
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


include('r25.php');

#Get rooms:

$query25 = array();

if ($_GET['building'] == 'EE1' || $_GET['building'] == 'EEB' || $_GET['building'] == 'ECE')
    $_GET['building'] = 'EEB'; #r25 has old code

$query25['short_name'] = $_GET['building'];

if (!isset($_GET['inactive'])) {
        $query25['category_id'] = '384'; # Campus - Seattle -- Upper Campus
        $query25['formal_name'] = 'Seattle-';
}

$feature_ids = array();
if (isset($_GET['feature'])) {
    foreach(array_keys($_GET['feature']) as $feature_id)
        $feature_ids[] = $feature_id;
}


$query25['feature_id'] = implode(',', $feature_ids);

#$includes[] = 'categories';
$includes[] = 'features';
#$includes[] = 'attributes';

$query25['scope'] = 'extended';
$query25['include'] = implode('+', $includes);

dprint_r($query25);

$spaces = r25_get('spaces', $query25);

dprint_r($spaces);

if (count($spaces)) {
    foreach ($spaces as $space) {
        $nameInfo = r25_decode_formal_name((string)$space->formal_name);
        $number = $nameInfo['number'];
        $roomname = $nameInfo['name'];

        try {
            $roomObj = Room::fromFacilityCodeAndRoomNumber($facility->FacilityCode, $number);
        } catch (Exception $e) {
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            include('error/404.php');
            exit();
        }

        dprint(print_r($roomObj, true));


        $room = $facility->FacilityCode . ' ' . $number;


        if ( $json ) {

            $results['room_list'][$room]['building_code'] = $facility->FacilityCode;
            $results['room_list'][$room]['room_name'] = $roomname;
            $results['room_list'][$room]['room_number'] = $number;
            $results['room_list'][$room]['room_capacity'] = (int)$space->max_capacity;
            $results['room_list'][$room]['room_type'] = ucfirst(strtolower($roomObj->Description));
            #$results['room_list'][$room]['room_notes'] = $row['Notes'];
            #$results['room_list'][$room]['room_notes'] = $roomObj->RoomType->Code;

        } else {

            if (! isset($currentFloor)) {
                $currentFloor = substr($number, 0, 1);
            }

            if (substr($number, 0, 1) != $currentFloor) {
                $currentFloor = substr($number, 0, 1);
            }
?>
  <option value="<?= $number ?>">
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
