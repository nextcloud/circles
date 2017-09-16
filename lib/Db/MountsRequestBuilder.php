<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

namespace OCA\Circles\Db;


use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Model\Cloud;
use OCA\Circles\Model\RemoteMount;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;

class MountsRequestBuilder extends CoreRequestBuilder {


	/** @var IGroupManager */
	protected $groupManager;

	/**
	 * CirclesRequestBuilder constructor.
	 *
	 * {@inheritdoc}
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, IGroupManager $groupManager,
		ConfigService $configService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $miscService);
		$this->groupManager = $groupManager;
	}


	/**
	 * Base of the Sql Insert request for Remote Mounts
	 *
	 * @return IQueryBuilder
	 */
	protected function getRemoteMountsInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_MOUNTS_REMOTE)
		   ->setValue('created', $qb->createFunction('NOW()'));

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Remote Mounts
	 *
	 * @return IQueryBuilder
	 */
	protected function getRemoteMountsSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'rm.circle_id', 'rm.remote_circle_id', 'rm.cloud_id', 'rm.token', 'rm.password',
			'rm.file_name', 'rm.file_id', 'rm.author', 'rm.mountpoint', 'rm.mountpoint_hash', 'rm.created'
		)
		   ->from(self::TABLE_MOUNTS_REMOTE, 'rm');

		$this->default_select_alias = 'rm';

		return $qb;
	}


	/**
	 * Right Join the Clouds table. Field is the SQL field that contain the cloudUniqueId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $field
	 **/
	protected function rightJoinClouds(IQueryBuilder &$qb, $field) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';

		$qb->from(self::TABLE_CLOUDS, 'cl')
		   ->addSelect('cl.cloud_id')
		   ->selectAlias('cl.address', 'cloud_address')
		   ->selectAlias('cl.status', 'cloud_status')
		   ->selectAlias('cl.note', 'cloud_note')
		   ->selectAlias('cl.created', 'cloud_created')
		   ->andWhere(
			   $expr->eq($pf . $field, 'cl.cloud_id')
		   );
	}



	/**
	 * @param array $data
	 *
	 * @return RemoteMount
	 */
	protected function parseRemoteMountsSelectSql(array $data) {
		$mount = new RemoteMount();

		$cloud = $this->parseCloudSelectSql($data);
		$mount->setCircleId($data['circle_id']);
		$mount->setRemoteCircleId($data['remote_circle_id']);
		$mount->setCloud($cloud);
		$mount->setToken($data['token']);
		$mount->setPassword($data['password']);
		$mount->setFileId($data['file_id']);
		$mount->setFilename($data['file_name']);
		$mount->setAuthor($data['author']);
		$mount->setMountPoint($data['mountpoint']);
		$mount->setMountPointHash($data['mountpoint_hash']);

		$mount->setCreated($data['created']);

		return $mount;
	}




	/**
	 * @param array $data
	 *
	 * @return Cloud
	 */
	protected function parseCloudSelectSql(array $data) {
		$cloud = new Cloud();

		$cloud->setCloudId($data['cloud_id']);
		$cloud->setAddress($data['cloud_address']);
		$cloud->setNote($data['cloud_note']);
		$cloud->setCreated($data['cloud_created']);

		return $cloud;
	}


}