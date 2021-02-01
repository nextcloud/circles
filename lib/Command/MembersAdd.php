<?php

declare(strict_types=1);


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


namespace OCA\Circles\Command;

use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\RequestResultNotJsonException;
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Request;
use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\IMember;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\CurrentUserService;
use OCA\Circles\Service\MemberService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersCreate
 *
 * @package OCA\Circles\Command
 */
class MembersAdd extends Base {


	/** @var CurrentUserService */
	private $currentUserService;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MembersCreate constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param CurrentUserService $currentUserService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		CircleRequest $circleRequest, CurrentUserService $currentUserService, MemberService $memberService,
		ConfigService $configService
	) {
		parent::__construct();
		$this->currentUserService = $currentUserService;
		$this->circleRequest = $circleRequest;

		$this->memberService = $memberService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:add')
			 ->setDescription('Add a member to a Circle')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addArgument('user', InputArgument::REQUIRED, 'username of the member')
			 ->addOption('viewer', '', InputOption::VALUE_REQUIRED, 'set a viewer', '')
			 ->addOption('type', '', InputOption::VALUE_REQUIRED, 'type of the user', Member::TYPE_USER);
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$viewerId = $input->getOption('viewer');
		$userId = $input->getArgument('user');
		$userType = $input->getOption('type');

		if ($viewerId !== '') {
			$this->currentUserService->setLocalViewer($viewerId);
		} else {
			$localCircle = $this->circleRequest->getCircle($circleId);
			if (!$this->configService->isLocalInstance($localCircle->getInstance())) {
				throw new Exception('the Circle is not managed from this instance, please use --viewer');
			}

			// TODO: manage NO_OWNER circles
			$owner = $localCircle->getOwner();
			$this->currentUserService->setCurrentUser($owner);
		}

		$member = $this->currentUserService->createCurrentUser($userId, (int)$userType);
		$this->memberService->addMember($circleId, $member);

////		$this->membersService->levelMember($circleId, $userId, DeprecatedMember::TYPE_USER, $instance, $level, true);
////
////		$member = $this->membersRequest->forceGetMember($circleId, $userId, DeprecatedMember::TYPE_USER, $instance);
////		echo json_encode($member, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}


	/**
	 * @param string $search
	 * @param string $instance
	 *
	 * @return string
	 */
	private function findUserFromLookup(string $search, string &$instance = ''): string {
		$userId = '';

		/** @var string $lookup */
		try {
			$lookup = $this->configService->getGSStatus(ConfigService::GS_LOOKUP);
		} catch (GSStatusException $e) {
			return '';
		}

		$request = new NC21Request(ConfigService::GS_LOOKUP_USERS, Request::TYPE_GET);
		$this->configService->configureRequest($request);
		$request->basedOnUrl($lookup);
		$request->addParam('search', $search);

		try {
			$users = $this->retrieveJson($request);
		} catch (
		RequestContentException |
		RequestNetworkException |
		RequestResultSizeException |
		RequestServerException |
		RequestResultNotJsonException $e
		) {
			return '';
		}

		$result = [];
		foreach ($users as $user) {
			if (!array_key_exists('userid', $user)) {
				continue;
			}

			list(, $host) = explode('@', $user['federationId']);
			if (strtolower($user['userid']['value']) === strtolower($search)) {
				$userId = $user['userid']['value'];
				$instance = $host;
			}

			$result[] = $user['userid']['value'] . ' <info>@' . $host . '</info>';
		}

//		if ($userId === '') {
//			foreach($result as $item) {
//				$output->writeln($item);
//			}
//		}

		return $userId;
	}

}

