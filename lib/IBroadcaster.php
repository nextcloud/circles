<?php


namespace OCA\Circles;

use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
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
	 * @param DeprecatedCircle $circle
	 */
	public function createShareToCircle(SharingFrame $frame, DeprecatedCircle $circle);


	/**
	 * broadcast an edition of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 * @param DeprecatedCircle $circle
	 */
	public function editShareToCircle(SharingFrame $frame, DeprecatedCircle $circle);


	/**
	 * broadcast a destruction of $share to $circleId.
	 *
	 * @param SharingFrame $frame
	 * @param DeprecatedCircle $circle
	 */
	public function deleteShareToCircle(SharingFrame $frame, DeprecatedCircle $circle);


	/**
	 * broadcast a creation of a Share to a $userId.
	 *
	 * @param SharingFrame $frame
	 * @param DeprecatedMember $member
	 */
	public function createShareToMember(SharingFrame $frame, DeprecatedMember $member);


	/**
	 * broadcast an edition of $share to $userId.
	 *
	 * @param SharingFrame $frame
	 * @param DeprecatedMember $member
	 */
	public function editShareToMember(SharingFrame $frame, DeprecatedMember $member);


	/**
	 * broadcast a destruction of $share to $userId.
	 *
	 * @param SharingFrame $frame
	 * @param DeprecatedMember $member
	 */
	public function deleteShareToMember(SharingFrame $frame, DeprecatedMember $member);
}
