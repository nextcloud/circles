<?php


namespace OCA\Circles;


use OCA\Circles\Model\SharingFrame;

interface IBroadcaster {

	/**
	 * Init the broadcaster
	 */
	public function init();

	/**
	 * broadcast $share to $userId.
	 *
	 * @param string $userId
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function broadcast(string $userId, SharingFrame $frame);

}