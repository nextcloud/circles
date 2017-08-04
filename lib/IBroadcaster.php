<?php


namespace OCA\Circles;


use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;

interface IBroadcaster {

	/**
	 * Init the broadcaster
	 */
	public function init();


	/**
	 * Init the broadcaster
	 */
	public function end();


	/**
	 * broadcast a creation of a Share to a circle.
	 *
	 * @param SharingFrame $frame
	 * @param Circle $circle
	 *
	 * @return
	 */
	public function createShareToCircle(SharingFrame $frame, Circle $circle);


	/**
	 * broadcast an edition of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 * @param Circle $circle
	 *
	 * @return
	 */
	public function editShareToCircle(SharingFrame $frame, Circle $circle);


	/**
	 * broadcast a destruction of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 * @param Circle $circle
	 *
	 * @return
	 */
	public function deleteShareToCircle(SharingFrame $frame, Circle $circle);


	/**
	 * broadcast a creation of a Share to a $userId.
	 *
	 * @param SharingFrame $frame
	 * @param Member $member
	 *
	 * @return
	 */
	public function createShareToMember(SharingFrame $frame, Member $member);


	/**
	 * broadcast an edition of $share to $userId.
	 *
	 * @param SharingFrame $frame
	 * @param Member $member
	 *
	 * @return
	 */
	public function editShareToMember(SharingFrame $frame, Member $member);


	/**
	 * broadcast a destruction of $share to $userId.
	 *
	 * @param SharingFrame $frame
	 * @param Member $member
	 *
	 * @return
	 */
	public function deleteShareToMember(SharingFrame $frame, Member $member);

}