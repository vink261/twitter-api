<?php

use Phalcon\Mvc\Model;

class UserTweetId extends Model
{

	public $id;

	public $user;

	public $target;

	public $last_tweet_id;

	public $last_like_id;

	public function initialize() {
		$this->useDynamicUpdate(true);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function getTarget()
	{
		return $this->target;
	}

	public function getLastTweetId()
	{
		return $this->last_tweet_id;
	}

	public function getLastLikeId()
	{
		return $this->last_like_id;
	}
}
