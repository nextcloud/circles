<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles;

/**
 * Class StatusCode
 *
 * @package OCA\Circles
 */
class StatusCode {
	public static $CIRCLE_CREATE = [
		120 => 'Duplicate name'
	];

	public static $CIRCLE_CONFIG = [
		120 => 'Invalid configuration'
	];

	public static $CIRCLE_JOIN = [
		121 => 'Team is full',
		122 => 'You are already a member',
		123 => 'Already requesting to join the team',
		124 => 'Team is closed'
	];

	public static $CIRCLE_LEAVE = [
		120 => 'You are not a member',
		121 => 'You are not a direct member'
	];

	public static $MEMBER_ADD = [
		120 => 'Unknown entity',
		121 => 'Team is full',
		122 => 'Already member of the team',
		123 => 'Already invited into the team',
		124 => 'Member is blocked',
		125 => 'The designed team cannot be added',
		126 => 'Team only accepts local users',
		127 => 'Remote Users are not accepted in a non-federated Team',
		128 => 'Cannot add Team as its own Member',
		129 => 'Member does not contains a patron',
		130 => 'Member is invited by an entity that does not belongs to the instance at the origin of the request',
		131 => 'Member is a non-local Team',
		132 => 'Member type not allowed'
	];

	public static $CIRCLE_DESTROY = [
		120 => 'Team is managed from an other app'
	];

	public static $MEMBER_LEVEL = [
		120 => 'The designed member\'s level is too high',
		121 => 'Incorrect Level'
	];

	public static $MEMBER_DISPLAY_NAME = [
		120 => 'DisplayName cannot be empty'
	];

	public static $MEMBER_REMOVE = [
		120 => 'The designed member\'s level is too high',
	];
}
