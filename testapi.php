<?php

include 'db.php';

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

$q = 'SELECT * FROM videos WHERE timeframe = "day" LIMIT 50';
$result = $db->query($q) or die($db->error . ': ' . $q);

$videos = [];
while ($row = $result->fetch_assoc()) {
	if (!isset($videos[$row['day']]))
		$videos[$row['day']] = [];

	$videos[$row['day']][] = $row['ytid'];
}

foreach ($videos as $date => $day) {
	echo '<a href="http://jumpjams.com/#v/' . implode(',', $day) . '">' . $date . '</a><br/>';
}

$db->close();

?>