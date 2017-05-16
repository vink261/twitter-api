<?php

require "../vendor/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;
use Phalcon\Mvc\Controller;

define('CONSUMER_KEY', '3rJOl1ODzm9yZy63FACdg');
define('CONSUMER_SECRET', '5jPoQ5kQvMJFDYRNE8bQ4rHuds4xJqhvgNJM4awaE8');


class TwitterController extends Controller
{
	private $users;
	private $password = "abcd1234";
	private $targets;

	public function initialize()
	{
		$this->getUser();
		$this->getTargetUser();
	}

	public function getUser()
	{
		$this->users = array('1junone', 'Leopikachu', 'Conynatsumi', 'MaidaHideki', 'SakitoTaihei', 'HideyoGatou', 'MoriokaFutoshi', 'TerumiIshikuno', 'EmikaYagawa', 'LeonPottere');
		return $this->users;
	}

	public function getTargetUser()
	{
		$this->targets = array('tokiokichi202', 'VinkJun');
		return $this->targets;
	}

	public function indexAction()
	{

	}

	public function getListFollowerAction()
	{
		$connection = $this->connectToApi($this->users[0]);
		echo '<pre>';

		var_dump($connection->get("followers/list", ["screen_name"=>"VinkJun",'skip_status'=>true]));
	}

	public function followAction()
	{
		foreach ($this->users as $user) {
			$connection = $this->connectToApi($user);
			foreach ($this->targets as $target) {
				$connection->post("friendships/create", ["screen_name" => $target, "follow" => true]);
				if ($connection->getLastHttpCode() == 200) {
					// Like posted succesfully
					echo 'follow done'."</br>";
				} else {
					// Handle error case
					echo $user.$target.'follow failed'."</br>";
				}
			}
		}
	}

	public function uploadAvatarAction()
	{
		$i = 2;
		$dir = $this->config->file->imagesDir;
		$images = scandir($dir);
//		$url = $this->url->get();
//		var_dump(base64_encode ("$url/images/$images[$i]"));exit;
		foreach ($this->users as $user) {
			$connection = $this->connectToApi($user);
			$connection->post(
				"account/update_profile_image",
				[
					"image"=>base64_encode (file_get_contents("$dir/$images[$i]"))
				]
			);
			if ($connection->getLastHttpCode() == 200) {
				// Like posted succesfully
				echo 'upload done' . "</br>";
			} else {
				// Handle error case
				echo  $connection->getLastHttpCode().'upload failed' . "</br>";
			}
			$i++;
		}
	}

	public function likeAction()
	{
		$like_flag = true;
		$data = $this->getTweetId();
		foreach ($this->users as $user) {
			$connection = $this->connectToApi($user);
			foreach ($data as $target => $ids) {
				$last_id = $this->getLastId($user, $target, false, $like_flag);
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
				//save last like id for user and target
				$this->saveLastId($user, $target, null, max($ids));
			}
		}
	}

	public function retweetAction()
	{
		$tweet_flag = true;
		//get tweet_id
		$data = $this->getTweetId();
		//retweet by each user
		foreach ($this->users as $user) {
			$connection = $this->connectToApi($user);
			foreach ($data as $target => $ids) {
				$last_id = $this->getLastId($user, $target, $tweet_flag);
				foreach ($ids as $id) {
					if ($last_id < $id) {
						$connection->post("statuses/retweet", ["id" => $id]);
						if ($connection->getLastHttpCode() == 200) {
							// Tweet posted succesfully
							echo 'retweet done' . "</br>";
						} else {
							// Handle error case
							echo 'retweet failed' . "</br>";
						}
					}
				}
				//save last tweet id for user and target
				$this->saveLastId($user,$target,max($ids));
			}
		}
	}

	public function getTweetId()
	{
		$connection = $this->connectToApi($this->users[0]);
		foreach ($this->targets as $target) {
			$res = $connection->get("statuses/user_timeline", ["screen_name" => $target, "include_rts" => 1, "count" => 100]);
			foreach ($res as $tweet) {
//				if (empty($tweet->entities->user_mentions)) {
					//for skip id to retweet or like
					$data[$target][] = $tweet->id_str;

//					$data[] = $tweet->id_str;
//				}
			}
		}
		return $data;
	}

	public function getLastId($user, $target, $tweet_flag = false, $like_flag = false)
	{
		$data = UserTweetId::findFirst([
				'user = ?0 AND target = ?1',
				'bind' => [$user, $target]
		]);
		if($data) {
			if($tweet_flag) return $data->last_tweet_id;
			if($like_flag) return $data->last_like_id;
		}
		return 0;
	}

	public function saveLastId($user, $target, $last_tweet_id = null, $last_like_id = null)
	{
		$data = UserTweetId::findFirst([
			'user = ?0 AND target = ?1',
			'bind' => [$user, $target]
		]);
		if($data) {
			if(!is_null($last_tweet_id)) $data->last_tweet_id = $last_tweet_id;
			elseif(!is_null($last_like_id)) $data->last_like_id = $last_like_id;
			$data->save();
		} else {
			$data = new UserTweetId();
			$data->user = $user;
			$data->target = $target;
			$data->last_tweet_id = $last_tweet_id;
			$data->last_like_id = $last_like_id;
			$data->create();
		}
	}

	public function connectToApi($user)
	{
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

	public function getToken($username, $password)
	{
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
