<?php

$json = isset($_GET['json']) && ($_GET['json'] || $_GET['json'] === '');
$inactive = isset($_GET['inactive']) && ($_GET['inactive'] || $_GET['inactive'] === '');

$args = array();
if (isset($_SERVER['PATH_INFO']))
  $args = explode( '/', $_SERVER['PATH_INFO'] );

if (count($args) > 1)
  $_GET['building'] = urldecode($args[1]);

$building_code = preg_replace("/[^a-zA-Z0-9]+/", "", $_GET['building']);

include('misc.php');

if ($building_code == 'EE1' || $building_code == 'EEB')
        $building_code = 'ECE';

require 'vendor/autoload.php';
use UW\SpaceWS\Facility;

include '/usr/local/etc/uw_ws/config.php';

# get building
try {
    /* Query the web services */
    $facility = Facility::fromFacilityCode($building_code);
} catch (Exception $e) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    include('error/404.php');
    goto MyExit;
}

// dprint_r($facility);

$results['building_code'] = $facility->FacilityCode;
$results['building_name'] = $facility->LongName;


include('r25.php');

#Get rooms:

$query25 = array();

if ($building_code == 'EE1' || $building_code == 'EEB' || $building_code == 'ECE')
    $building_code = 'EEB'; #r25 has old code

$query25['short_name'] = $building_code;

if (!isset($_GET['inactive'])) {
        $query25['category_id'] = '384'; # Campus - Seattle -- Upper Campus
#        $query25['formal_name'] = 'Seattle-';
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

        dprint("Looking for {$space->formal_name}");
        if ($nameInfo = r25_decode_formal_name((string)$space->formal_name)) {
            dprint_r($nameInfo);
            $number = $nameInfo['number'];
            $roomname = $nameInfo['name'];
        } elseif ($nameInfo = r25_decode_space_name((string)$space->space_name)) {
            $roomname = '';
            $number = $nameInfo['number'];
        }

        dprint_r($nameInfo);

        $room = $facility->FacilityCode . ' ' . $number;

        foreach($space->feature as $featureObj) {
            $feature = array_merge((array)$featureObj, r25_decode_feature_name((string)$featureObj->feature_name));

            $attribute = array(
                               'name' => $feature['display_name'],
                               'quantity' => $feature['quantity'],
        #                       'length' => $row['Length'],
        #                       'width' => $row['Width'],
        #                       'notes' => $row['Notes'],
                       );
            $results['room_list'][$room]['attribute_list'][$feature['category']][] = $attribute;
        }

        if ($results['room_list'][$room]['attribute_list']) {
            ksort($results['room_list'][$room]['attribute_list']);
        }

        if ( $json ) {

            $results['room_list'][$room]['building_code'] = $facility->FacilityCode;
            $results['room_list'][$room]['room_name'] = $roomname;
            $results['room_list'][$room]['room_number'] = $number;
            $results['room_list'][$room]['room_capacity'] = (int)$space->max_capacity;
            #$results['room_list'][$room]['room_notes'] = $row['Notes'];

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

if (! $json) {
    exit();
}

    $results['service_urls']['Schedule a tutorial'] = 'https://itconnect.uw.edu/learn/technology-training/equipment/';
    $results['service_urls']['Report a problem'] = "http://www.washington.edu/classroom/problem/?building={$results['building_code']}";

    $results['service_urls']['Special event request'] = 'https://www.cte.uw.edu/eventservices/room-request/';

    if ($facility && $facility->CenterPointLongitude != 0 && $facility->CenterPointLatitude != 0) {
        $results['access_url'] = sprintf(
            "https://depts.washington.edu/ceogis/Public/Accessibility/Map/?marker=%f,%f,,%s&level=%d",
            $facility->CenterPointLongitude,
            $facility->CenterPointLatitude,
            rawurlencode($results['building_name']),
            3
        );
    } elseif ($facility && $facility->FacilityNumber) {
        // Don't know if this works at all
        $results['access_url'] = sprintf(
            "https://depts.washington.edu/ceogis/Public/Accessibility/Map/?query=%s,%s,%d",
            'Building%20Information',
            'FacilityNumber',
            $facility->FacilityNumber
        );
    } else {
        // Don't know if this works at all
        $results['access_url'] = sprintf("https://depts.washington.edu/ceogis/Public/Accessibility/Map/?query=%s,%s,%s",
                                       'Building%20Information', 'FacilityCode', $results['building_code']);
    }

MyExit:
if (! isset($_GET['debug']))
    exit(json_encode($results));

?>
<form method="POST"><input type="submit" name="update_cache" value="Reload Cached Entries" /></form>
<pre><?= htmlentities(json_encode($results, JSON_PRETTY_PRINT)) ?></pre>
<hr />
<pre><?= $debug_output ?></pre>
