<?php


/**
 * GithubActivity
 * @author  Devin Clark <dclarkdesign@gmail.com>
 */
class GithubActivity {
	/**
	 * The Github Username
	 * @var String
	 * @access private
	 */
	private $username;
	/**
	 * Number of items to show.
	 * @var Integer
	 * @access private
	 */
	private $numberOfItems;
	/**
	 * The data pulled from the API formatted as an associative array.
	 * @var Array
	 * @access private
	 */
	private $data;
	/**
	 * The Name of the transient. Defaults to github_$username.
	 * @var String
	 * @access private
	 */
	private $transient_name;
	/**
	 * The amount of time to keep the transient data cached.
	 * @var Integer
	 * @access  private
	 */
	private $transient_time;

	/**
	 * Instantiates the class.
	 * @param String $username The Github username
	 * @param Integer $number   The number of posts to retrieve
	 */
	function __construct($username, $number) {
		$this->username = $username;
		$this->numberOfItems = $number;
		if(function_exists('get_transient')) {
			$this->transient_name = "github_$username";
			$this->transient_time = 3 * 60 * 60;
			$this->data = $this->cache_data();
		}
		else {
			$this->data = $this->get_feed_data("https://api.github.com/users/{$this->username}/events");
		}
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

	/**
	 * Accessor/Mutator for $username
	 * @param  String $val The value of username to set
	 * @return String      The value of username if $val is not set.
	 */
	public function username($val = null) {
		if($val !== null)
			$username = $val;
		else
			return $username;
	}

	/**
	 * A function to cache the data retrieved from the API in WordPress
	 * @return Array An associative array of the data.
	 */
	private function cache_data() {
		if(false === ($output = get_transient($this->transient_name))) {
			$output = $this->get_feed_data("https://api.github.com/users/{$this->username}/events");
			set_transient($this->transient_name, $output, $this->transient_time);
		}
		return $output;
	}

	/**
	 * Formats the item passed as a list item.
	 * @param  Array $item A single item of $data.
	 * @return String|Boolean       The formatted string or false on error
	 */
	private function format_item($item) {
		ob_start();
		if($item == "") {
			return false;
		}

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
				if($item['payload']['ref'] == "master" && $item['payload']['ref_type'] == "branch") {
					return false;
				}
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

		if(function_exists('human_time_diff')) {
			echo " " . human_time_diff(strtotime($item["created_at"])) . " ago";
		}
		else {
			echo " " . date('M jS g:i a', strtotime($item["created_at"]));
		}

		return ob_get_clean();
	}

	/**
	 * Formats each item and returns it
	 * @return string An unordered list of the formatted items.
	 */
	public function __toString() {
		ob_start();
		echo "<ul>";
		if($this->data == null) {
			echo "<li>No data... or the Github API is down... :(</li>";
		} 
		else {
			$number_of_items_returned = 0;
			for($i = 0; $i < count($this->data); $i++) {
				if($number_of_items_returned >= ($this->numberOfItems)) {
					break;
				}
				else {
					$content = $this->format_item($this->data[$i]);
					if($content == false || $content == "") {
						continue;
					}
					else {
						if($content !== "<li>") {
							$number_of_items_returned++;
							echo "<li>$content</li>";
						}
						else {
							continue;
						}
					}
				}
			}
		}
		echo "</ul>";
		return ob_get_clean();
	}
}