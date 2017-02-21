<?php

include 'db.php';

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

$q = 'SELECT * FROM videos WHERE timeframe = "day"';
$result = $db->query($q) or die($db->error . ': ' . $q);

$videos = [];
while ($row = $result->fetch_assoc()) {
	if (!isset($videos[$row['day']]))
		$videos[$row['day']] = [];

	$videos[$row['day']][] = $row['ytid'];
}

foreach ($videos as $date => $day) {
	$ids = implode(',', array_slice($day, 0, 50));
	echo '<a href="http://jumpjams.com/#v/' . $ids . '">' . $date . '</a><br/>';
}

$db->close();

?>