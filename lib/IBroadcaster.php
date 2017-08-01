<?php


namespace OCA\Circles;


use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;

interface IBroadcaster {

	/**
	 * Init the broadcaster
	 */
	public function init();


	/**
	 * broadcast a creation of a Share to a circle.
	 *
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function createShareToCircle(SharingFrame $frame);


	/**
	 * broadcast a creation of a Share to a $userId.
	 *
	 * @param SharingFrame $frame
	 * @param string $userId
	 *
	 * @return
	 */
	public function createShareToUser(SharingFrame $frame, $userId);


	/**
	 * broadcast a destruction of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function deleteShareToCircle(SharingFrame $frame);


	/**
	 * broadcast a destruction of $share to $userId.
	 *
	 * @param SharingFrame $frame
	 * @param string $userId
	 *
	 * @return
	 */
	public function deleteShareToUser(SharingFrame $frame, $userId);


	/**
	 * broadcast an edition of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function editShareToCircle(SharingFrame $frame);


	/**
	 * broadcast an edition of $share to $userId.
	 *
	 * @param SharingFrame $frame
	 * @param string $userId
	 *
	 * @return
	 */
	public function editShareToUser(SharingFrame $frame, $userId);

}