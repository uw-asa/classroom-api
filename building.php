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

header("Access-Control-Allow-Origin: *");

if ($building_code == 'EE1' || $building_code == 'EEB')
        $building_code = 'ECE';

$results['building_code'] = $building_code;


include('r25.php');

#Get rooms:

$query25 = array();

$query25['short_name'] = $building_code;

if (!isset($_GET['inactive'])) {
    $query25['category_id'] = implode(',', array(
        '186', # Type - 110 - General Classroom (Central Assignment)
        '384', # Campus - Seattle -- Upper Campus
    ));
}

$feature_ids = array();
if (isset($_GET['feature'])) {
    foreach(array_keys($_GET['feature']) as $feature_id)
        $feature_ids[] = $feature_id;
}


$query25['feature_id'] = implode(',', $feature_ids);

#$includes[] = 'categories';
$includes[] = 'features';
$includes[] = 'attributes';

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
            $roomname = $nameInfo['name'];
            $results['building_name'] = $nameInfo['building'];
        } else {
            $results['building_name'] = (string)$space->formal_name;
            $roomname = '';
        }
        $nameInfo = r25_decode_space_name((string)$space->space_name);
        $number = $nameInfo['number'];

        dprint_r($nameInfo);

        $room = $building_code . ' ' . $number;

        if ($room == 'FTR 034') { continue; } // not managed by AT

        foreach($space->feature as $featureObj) {
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
            $results['room_list'][$room]['attribute_list'][$feature['category']][] = $attribute;
        }

        ksort($results['room_list'][$room]['attribute_list']);

        foreach ($space->custom_attribute as $attribute) {
            switch ($attribute->attribute_name) {
                case 'Latitude':
                case 'Longitude':
                case 'MAP':
                    // Building attributes
                    $results[strtolower($attribute->attribute_name)] = (string)$attribute->attribute_value;
                    break;
            }
        }

        if ( $json ) {

            $results['room_list'][$room]['building_code'] = $building_code;
            $results['room_list'][$room]['room_name'] = $roomname;
            $results['room_list'][$room]['room_number'] = $number;
            $results['room_list'][$room]['room_capacity'] = (int)$space->max_capacity;
            $results['room_list'][$room]['price_group'] = '';
            $results['room_list'][$room]['room_notes'] = '';

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

    $results['service_urls']['Equipment tutorial'] = 'https://itconnect.uw.edu/learn/technology-training/equipment/';
    $results['service_urls']['Report a problem'] = "https://academictechnologies.asa.uw.edu/room-problem/?building={$results['building_code']}";
    $results['service_urls']['Special event request'] = 'https://eventservices.uw.edu/room-request/';

    switch ($results['building_code']) {
        case 'AND':
        case 'CSE2':
        case 'CDH':
        case 'HRC':
        case 'LOW':
        case 'OUG':
        case 'PAA':
        case 'PAB':
        case 'PAR':
        case 'WFS':
            if ($results['longitude'] && $results['latitude']) {
                $results['access_url'] = sprintf(
                    "https://depts.washington.edu/ceogis/Public/Accessibility/Map/?marker=%f,%f,,%s&level=%d",
                    $results['longitude'],
                    $results['latitude'],
                    rawurlencode($results['building_name']),
                    3
                );
            }
            break;
        default:
            $results['access_url'] = sprintf(
                "https://depts.washington.edu/ceogis/Public/Accessibility/Map/?query=%s,%s,%s",
                'Building%20Information',
                'FacilityCode',
                $results['building_code']
            );
            break;
    }

MyExit:
if (! isset($_GET['debug']))
    exit(json_encode($results));

?>
<form method="POST"><input type="submit" name="update_cache" value="Reload Cached Entries" /></form>
<pre><?= htmlentities(json_encode($results, JSON_PRETTY_PRINT)) ?></pre>
<hr />
<pre><?= $debug_output ?></pre>
