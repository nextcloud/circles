<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Migration;

use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Db\SharesRequest;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Service\ConfigService;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class ImportOwncloudCustomGroups
 *
 * @package OCA\Circles\Migration
 */
class RemoveDeadShares implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var  IConfig */
	protected $config;

	/** @var SharesRequest */
	private $sharesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoveDeadShares constructor.
	 *
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 * @param SharesRequest $sharesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		IDBConnection $connection, IConfig $config, SharesRequest $sharesRequest,
		DeprecatedMembersRequest $membersRequest, ConfigService $configService
	) {
		$this->connection = $connection;
		$this->config = $config;

		$this->sharesRequest = $sharesRequest;
		$this->membersRequest = $membersRequest;
		$this->configService = $configService;
	}


	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Cleaning shares database of dead shares';
	}


	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {

		$members = $this->membersRequest->forceGetAllMembers();

		foreach ($members as $member) {
			if ($member->getLevel() > DeprecatedMember::LEVEL_NONE) {
				continue;
			}

			if ($member->getType() === DeprecatedMember::TYPE_USER) {
				$this->removeSharesFromMember($member);
			}

			try {
				$this->membersRequest->removeMember($member);
			} catch (\Exception $e) {
			}
		}

	}


	private function removeSharesFromMember(DeprecatedMember $member) {
		$this->sharesRequest->removeSharesFromMember($member);
	}


}
