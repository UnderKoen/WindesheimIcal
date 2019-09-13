<?php
$classes = explode(",", $_GET["classes"]);
$events = array();
$valid = false;

foreach ($classes as $class) {
    if (!isset($class) || empty($class)) continue;

    $check = file_get_contents("http://api.windesheim.nl/api/Klas/$class");
    if ($check == "null") continue;

    $rooster = json_decode(file_get_contents("http://api.windesheim.nl/api/Klas/$class/Les"), true);
    foreach ($rooster as $event) {
        if (!$valid) $valid = true;

        array_push($events, $event);
    }
}

if (!$valid) {
    http_response_code(404);
    die();
}

function longToDate($long) {
    $seconds = $long / 1000;
    $date = new DateTime("@$seconds", new DateTimeZone("Europe/Amsterdam"));
    return "TZID=Europe/Amsterdam:" . $date->format("Ymd\THis");
}


header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename=calendar.ics');

echo "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Under_Koen//NONSGML Windesheim ICAL//EN
";

foreach ($events as $event) {
    echo "BEGIN:VEVENT
UID:" . $event["id"] . "@" . $_SERVER["HTTP_HOST"] . "
DTSTAMP;TZID=Europe/Amsterdam:" . gmdate("Ymd\THis") . "
DTSTART;" . longToDate($event["starttijd"]) . "
DTEND;" . longToDate($event["eindtijd"]) . "
SUMMARY:" . $event["commentaar"] . "
";

    if (!empty($event["docentnamen"])) {
        $docenten = "";
        foreach ($event["docentnamen"] as $docentnaam) {
            if (!empty($docenten)) $docenten = "$docenten, ";
            $docenten = "$docenten$docentnaam";
        }
        echo "DESCRIPTION:Klas: " . $event["groepcode"] . "\\nDocent(en): $docenten\n";
    } else {
        echo "DESCRIPTION:Klas: " . $event["groepcode"] . "\n";
    }

echo "LOCATION:" . $event["lokaal"] . "
END:VEVENT
";
}

echo "END:VCALENDAR
";
exit();