<?php


namespace OCA\Circles;


use OCA\Circles\Model\Share;

interface IBroadcaster {

	/**
	 * Init the broadcaster
	 */
	public function init();

	/**
	 * broadcast $share to $userId.
	 *
	 * @param string $userId
	 * @param Share $share
	 */
	public function broadcast(string $userId, Share $share);

}