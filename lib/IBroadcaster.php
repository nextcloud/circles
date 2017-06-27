<?php


namespace OCA\Circles;


use OCA\Circles\Model\SharingFrame;

interface IBroadcaster {

	/**
	 * Init the broadcaster
	 */
	public function init();

	/**
	 * broadcast a creation of $share to $userId.
	 *
	 * @param string $userId
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function createShareToUser($userId, SharingFrame $frame);



	/**
	 * broadcast a creation of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function createShareToCircle(SharingFrame $frame);


	/**
	 * broadcast a destruction of $share to $userId.
	 *
	 * @param string $userId
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function deleteShareToUser($userId, SharingFrame $frame);


	/**
	 * broadcast a destruction of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function deleteShareToCircle(SharingFrame $frame);


	/**
	 * broadcast an edition of $share to $userId.
	 *
	 * @param string $userId
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function editShareToUser($userId, SharingFrame $frame);


	/**
	 * broadcast an edition of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 *
	 * @return
	 */
	public function editShareToCircle(SharingFrame $frame);

}