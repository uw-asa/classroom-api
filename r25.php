<?php

require "/usr/local/etc/r25/config.php";


function r25_get($r25_table, $r25_query, $timeout=2880)
{
    global $r25_baseurl, $r25_username, $r25_password;

    $cred = sprintf('Authorization: Basic %s',
                    base64_encode( $r25_username . ':' . $r25_password ) );

    $opts = array(
                  'http'=>array(
                                'method'=>'GET',
                                'header'=>"$cred\r\n",
                                ));

    $ctx = stream_context_create($opts);

    $url = $r25_baseurl . $r25_table . '.xml?' . http_build_query($r25_query, '', '&', PHP_QUERY_RFC3986);

    if ( isset($_POST['update_cache']) || !( $text = apc_fetch($url) ) ) {
        $text = file_get_contents( $url, false, $ctx );

        apc_store($url, $text, $timeout);
    }

    return simplexml_load_string($text, 'SimpleXMLElement', 0, 'http://www.collegenet.com/r25');
}

function r25_get_space_by_short_name($short_name)
{
    return r25_get('spaces', array('short_name' => $short_name, 'scope' => 'extended'))->space;
}




function r25_decode_feature_name($longname)
{
    $longname = preg_replace('/^Tables- /', 'Tables - ', $longname); # normalize
    $longname = preg_replace('/\(.*\)/', '', $longname); #remove parentheticals
    $longname = trim($longname);

    preg_match('/(?P<category>.+?) - (?P<name>.+)/', $longname, $matches);

    if ($matches) {
        $category = $matches['category'];
        switch ($category) {
        case 'ADA':
        case 'AV':
        case 'Facilities':
        case 'Furniture Type':
        case 'Room Type':
            $display_name = $matches['name'];
            break;
        case 'Board':
        case 'Chairs':
        case 'Lectern':
        case 'Tables':
        default:
            $display_name = $longname;
            break;
        }
    } else {
        $display_name = $longname;
        switch ($display_name) {
        case 'Lectern/Podium/Stand':
            $category = 'Lectern';
            break;
        case 'Tables':
            $category = 'Tables';
            break;
        case 'Piano':
            $category = 'AV';
            break;
        default:
            $category = 'Facilities';
            break;
        }
    }

    return array('category' => $category,
                 'display_name' => $display_name);
}

function r25_decode_formal_name($longname)
{
    preg_match('/(?P<campus>.+)- (?P<building>.+) (?P<number>[A-Z\d]{3,5})(\s+\((?P<name>.+)\))?/', $longname, $matches);

    if (!count($matches))
        return false;

    if (!array_key_exists('name', $matches))
        $matches['name'] = null;

    switch ($matches['building']) {
    case 'Electrical Engineering Building':
        $matches['building'] = 'Electrical and Computer Engineering Building';
        break;
    default:
        break;
    }

    return array('campus' => $matches['campus'],
                 'building' => $matches['building'],
                 'number' => $matches['number'],
                 'name' => $matches['name']);
}

function r25_canonicalize_room_number($room_number)
{
    preg_match('/^\s*(?P<prefix>[^\d]?)(?P<number>\d+)(?P<suffix>[^\d]?)\s*$/', $room_number, $matches);

    return sprintf('%s%03d%s', $matches['prefix'], $matches['number'], $matches['suffix']);
}

function r25_decode_space_name($space_name)
{
    list($building_code, $room_number) = preg_split('/\s+/', $space_name);

    return array('building_code' => $building_code,
                 'number' => $room_number);
#                 'number' => r25_canonicalize_room_number($room_number));

}


?>
