<?php

use Phalcon\Mvc\Controller;

class TwitterController extends Controller
{

	public function indexAction()
	{
		$username = "VinkJun";
		$password = "toiyeuem261";
		$res = $this->getToken($username, $password);
		echo "<pre>";
		print_r($res);
		echo "<pre>";
		exit;
	}
	public function getToken($username, $password) {
		$sn = $username; //Your Twitter name
		$pw = $password; // Your Twitter Password
		$ck = '3rJOl1ODzm9yZy63FACdg'; //consumer_key
		$cs = '5jPoQ5kQvMJFDYRNE8bQ4rHuds4xJqhvgNJM4awaE8'; //consumer_key_secret
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
