<?php

class GithubActivity {
	/** Instance Variables */
	private $username;
	private $numberOfItems;
	private $data;
	private $transient_name;
	private $transient_time;

	/** Constructor(s) */
	function __construct($username, $number) {
		$this->username = $username;
		$this->numberOfItems = $number;
		$this->transient_name = "github_$username";
		$this->transient_time = 3 * 60 * 60;
		$this->data = $this->cache_data();
	}

	/**
	 * Uses cURL to get the contents of the JSON API files and converts the contents to an array.
	 *
	 * @param string     $url The URL of a file.
	 * @access private
	 * @return Array    The contents of the file passed in.
	 */
	private function get_feed_data($url) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return json_decode($data, true);
	}

	/* Accessor & Mutator Method */
	public function username($val = null) {
		if($val !== null)
			$username = $val;
		else
			return $username;
	}

	public function cache_data() {
		if(false === ($output = get_transient($this->transient_name))) {
			$output = $this->get_feed_data("https://api.github.com/users/{$this->username}/events");
			set_transient($this->transient_name, $output, $this->transient_time);
		}
		return $output;
	}

	public function format_item($item) {
		ob_start();
		if($item == "") {
			return false;
		}
		//echo "<pre>"; print_r($item); echo "</pre>";
		echo "<li>";
		switch($item["type"]) {
			case "WatchEvent":
				echo "Watched ";
				break;
			case "PushEvent":
				echo "Pushed to ";
				break;
			case "PullRequestEvent":
				echo "Opened a pull request ";
				break;
			case "ForkEvent":
				echo "Forked ";
				break;
			case "FollowEvent":
				echo "Followed <a href='" . $item['payload']['html_url'] . "'>" . $item['payload']['login'] . "</a>";
				break;
			case "CreateEvent":
				echo "Created ";
				break;
			case "IssueCommentEvent":
				echo "Commented on an issue <a href='{$item['payload']['issue']['html_url']}'>" . $item['repo']['name'] . "</a>";
				break;
			default:
				return false;
		}
		$special_output_events = array("FollowEvent", "IssueCommentEvent");
		if(in_array($item["type"], $special_output_events) == false) {
			echo "<a href='https://github.com/{$item['repo']['name']}'>" . $item['repo']['name'] . "</a>";
		}
		echo " " . human_time_diff(strtotime($item["created_at"])) . " ago";
		echo "</li>";
		return ob_get_clean();
	}

	public function __toString() {
		ob_start();
		//echo "<pre>"; print_r($this->data[0]); echo "</pre>";
		echo "<ul>";
		if($this->data == null) {
			echo "<li>No data... or the Github API is down... :(</li>";
		} 
		else {
			$number_of_items_returned = 0;
			for($i = 0; $i < count($this->data); $i++) {
				if($number_of_items_returned >= ($this->numberOfItems - 1)) {
					break;
				}
				else {
					$content = $this->format_item($this->data[$i]);
					if($content == false) {
						continue;
					}
					else {
						$number_of_items_returned++;
						echo $content;
					}
				}
			}
		}
		echo "</ul>";
		return ob_get_clean();
	}
}