<?php
date_default_timezone_set('America/Los_Angeles');
require('db.php');
define('REDDIT_MUSIC_SEARCH_URL', 'https://www.reddit.com/r/Music/search/.json');

class RedditMusic {
	private $options = [
				'q' => 'flair%3A"music+streaming"+OR+flair%3A"new+release"+OR+flair%3A"video"',
				'sort' => 'top',
				'limit' => '100',
				't' => 'day',
				'restrict_sr' => 'on'
			];
	public function __construct($options = []) {
		foreach ($options as $i => $op) {
			$this->options[$i] = $op;
		}
	}
	public function getDay() {
		$this->getSongs('day');
	}
	public function getWeek() {
		$this->getSongs('week');
	}
	public function getMonth() {
		$this->getSongs('month');
	}
	public function getYear() {
		$this->getSongs('year');
	}
	public function getSongs($timeframe) {
		$this->setOption('t', $timeframe);
		$songs = $this->search()->data->children;
		$songs = $this->parseSongs($songs);
		//print_r($songs);
		foreach ($songs as $i => $song) {
			$songs[$i]['timeframe'] = $timeframe;
		}
		$this->insertSongs($songs);
	}
	public function setOption($option, $value) {
		$this->options[$option] = $value;
	}
	private function parseSongs($songs) {
		$parsed = [];
		foreach ($songs as $i => $song) {
			if (isset($song->data->media->oembed) && $song->data->media->oembed->provider_name == 'YouTube') {
				$parsed[] = $this->parseSong($song);
			}
		}
		return $parsed;
	}
	private function parseSong($song) {
		// Parse Title Components.
		$title_info = $this->parseTitleComponents($song->data->title);

		// Parse YTID.
		$ytid = $this->parseYTID($song->data->media->oembed->thumbnail_url);

		return [
			'rid' => $song->data->id,
			'gilded' => $song->data->gilded,
			'author' => $song->data->author,
			'score' => $song->data->score,
			'created' => date('Y-m-d H:i:s', $song->data->created),
			'yturl' => $song->data->url,
			'title' => $title_info['title'],
			'genre' => $title_info['genre'],
			'artist' => $title_info['artist'],
			'song' => $title_info['song'],
			'thumb' => $song->data->media->oembed->thumbnail_url,
			'ytid' => $ytid,
			'day' => date('Y-m-d')
		];
	}
	private function parseYTID($thumbnail_url) {
		preg_match('/vi\/([a-zA-Z0-9\-_]{11})/', $thumbnail_url, $m);
		if (isset($m[1])) {
			return $m[1];
		}
		return '';
	}
	private function parseTitleComponents($title) {
		$genre = '';
		preg_match_all('/\[([^\]]+)\]/', $title, $m);
		$n = count($m[1]);
		if ($n) {
			$genre = $m[1][$n-1];
			$title = preg_replace('/\[([^\]]+)\]/', '', $title);
		}
		$info = $this->parseArtistSong($title);

		return [
			'title' => $title,
			'genre' => $genre,
			'artist' => $info['artist'],
			'song' => $info['song']
		];
	}
	private function parseArtistSong($title) {
		preg_match('/(.+?)\s-\s(.+)/', $title, $m);
		$artist = '';
		$song = '';
		if (isset($m[1])) {
			$artist = $m[1];
			$song = $m[2];
		}

		return [
			'artist' => $artist,
			'song' => $song
		];
	}
	private function insertSongs($songs) {
		$q = 'INSERT IGNORE INTO videos ';

		// Fields.
		$fields = '';
		foreach ($songs[0] as $field => $val) {
			$fields .= $field . ',';
		}
		$fields = rtrim($fields, ',');

		// Values.
		$values = '';
		foreach ($songs as $song) {
			$values .= '(';
			foreach ($song as $field => $val) {
				
				$values .= '"' . addslashes($val) . '",';
			}
			$values = rtrim($values, ',') . '),';
		}
		
		$values = rtrim($values, ',');

		$q .= '(' . $fields . ') VALUES ' . $values;
		$this->query($q);
		//echo $q;
	}
	private function search () {
		$options = [];
		foreach ($this->options as $key => $val) {
			$options[] = $key . '=' . $val;
		}

		$url = REDDIT_MUSIC_SEARCH_URL . '?' . implode('&', $options);
		$json = file_get_contents($url);
		return json_decode($json);
	}
	private function query($s) {
		$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
		$result = $db->query($s) or die($db->error . ': ' . $s);

		$db->close();
		return $result;
	}
}

?>