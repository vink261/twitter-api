<?php

require "../vendor/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;
use Phalcon\Mvc\Controller;

define('CONSUMER_KEY', '3rJOl1ODzm9yZy63FACdg');
define('CONSUMER_SECRET', '5jPoQ5kQvMJFDYRNE8bQ4rHuds4xJqhvgNJM4awaE8');


class TwitterController extends BaseController
{
	private $users;
	private $password = "abcd1234";
	private $targets;


	public function initialize() {
		$this->getUser();
		$this->getTargetUser();
	}

	public function getUser() {
		$this->users = array('1junone', 'Leopikachu', 'Conynatsumi');
		return $this->users;
	}

	public function getTargetUser() {
		$this->targets = array('tokiokichi202', 'VinkJun');
		return $this->targets;
	}

	public function indexAction()
	{

	}

	public function likeAction() {
		$data = $this->getTweetId();
		$i =0;
		foreach ($this->users as $user) {
			$connection = $this->connectToApi($user);
			foreach ($data as $target => $ids) {
				$last_id = $this->getLastId($user, $target);
				foreach ($ids as $id) {
					if ( $last_id < $id ) {
						$connection->post("favorites/create", ["id" => $id]);
						if ($connection->getLastHttpCode() == 200) {
							// Like posted succesfully
							echo 'like done'."</br>";
						} else {
							// Handle error case
							echo $id.'like failed'."</br>";
						}
					}
				}
				//save last tweet id for user and target
				$this->saveLastId($user,$target,max($ids));
			}
		}
	}

	public function retweetAction() {
		//get tweet_id
		$tweet_ids = $this->getTweetId();
		//retweet by each user
		foreach ($this->users as $user) {
			$connection = $this->connectToApi($user);
			foreach ($tweet_ids as $id){
				$connection->post("statuses/retweet", ["id" => $id]);
				if ($connection->getLastHttpCode() == 200) {
					// Tweet posted succesfully
					echo 'retweet done'."</br>";
				} else {
					// Handle error case
					echo 'retweet failed'."</br>";
				}
			}
		}
	}

	public function getTweetId() {
		$connection = $this->connectToApi($this->users[0]);
		foreach ($this->targets as $target) {
			$res = $connection->get("statuses/user_timeline", ["screen_name" => $target]);
			foreach ($res as $tweet) {
				if (empty($tweet->entities->user_mentions)) {
					//for skip id to retweet or like
					$data[$target][] = $tweet->id_str;

//					$data[] = $tweet->id_str;
				}
			}
		}
		return $data;
	}

	public function getLastId($user, $target) {
		$data = UserTweetId::findFirst([
				'user = ?0 AND target = ?1',
				'bind' => [$user, $target]
		]);
		return $data ? $data->last_tweet_id : 0;
	}

	public function saveLastId($user, $target, $tweet_id) {
		$data = UserTweetId::findFirst([
			'user = ?0 AND target = ?1',
			'bind' => [$user, $target]
		]);
		if($data) {
			$data->last_tweet_id = $tweet_id;
			$data->save();
		} else {
			$data = new UserTweetId();
			$data->user = $user;
			$data->target = $target;
			$data->last_tweet_id = $tweet_id;
			$data->create();
		}
	}

	public function connectToApi($user) {
		//set cache key
		$cacheKey = "$user.connection";

		//if connected
		if($this->cache->get($cacheKey)){
			//0.02s per process
			$connection = $this->cache->get($cacheKey);
		} else {
			//0.6s per access
			//require token by username and password
			$res = $this->getToken($user, $this->password);
			$oauth_token = $res->oauth_token;
			$oauth_token_secret = $res->oauth_token_secret;
			//use library to connect to api
			$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $oauth_token, $oauth_token_secret);
			//save connection to cache
			$this->cache->save($cacheKey, $connection);
		}

		return $connection;
	}

	public function getToken($username, $password) {
		$sn = $username; //Your Twitter name
		$pw = $password; // Your Twitter Password
		$ck = CONSUMER_KEY; //consumer_key
		$cs = CONSUMER_SECRET; //consumer_key_secret
		$url = 'https://api.twitter.com/oauth/access_token';
		$params = array(
			'x_auth_mode'     => 'client_auth',
			'x_auth_username' => $sn,
			'x_auth_password' => $pw,
		);
		$oauth = array(
			'oauth_consumer_key'     => $ck,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp'        => time(),
			'oauth_version'          => '1.0a',
			'oauth_nonce'            => md5(mt_rand()),
			'oauth_token'            => '',
		);
		$key = array($cs,'');
		$base = $oauth + $params;
		uksort($base, 'strnatcmp');
		$oauth['oauth_signature'] = base64_encode(hash_hmac(
			'sha1',
			implode('&', array_map('rawurlencode', array(
				'POST',
				$url,
				str_replace(
					array('+', '%7E'),
					array('%20', '~'),
					http_build_query($base, '', '&')
				),
			))),
			implode('&', array_map('rawurlencode', $key)),
			true
		));
		$items = array();
		foreach ($oauth as $key => $value) {
			$items[] = urlencode($key) . '="' . urlencode($value) . '"';
		}
		$header =  array(
			'Authorization: OAuth ' . implode(', ', $items)
		);
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_URL        => $url,
			CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
			CURLOPT_POST       => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_ENCODING       => 'gzip'
		));
		parse_str(curl_exec($ch), $res);
		$res = (object)$res;
		return $res;
	}

}
