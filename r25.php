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

    return array('campus' => $matches['campus'],
                 'building' => $matches['building'],
                 'number' => $matches['number'],
                 'name' => $matches['name']);
}

?>
