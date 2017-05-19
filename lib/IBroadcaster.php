<?php


namespace OCA\Circles;


use OCA\Circles\Model\Frame;

interface IBroadcaster {

	/**
	 * Init the broadcaster
	 */
	public function init();

	/**
	 * broadcast $share to $userId.
	 *
	 * @param string $userId
	 * @param Frame $frame
	 *
	 * @return
	 */
	public function broadcast(string $userId, Frame $frame);

}