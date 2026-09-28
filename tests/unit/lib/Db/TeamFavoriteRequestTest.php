<?php

declare(strict_types=1);

namespace OCA\Circles\Tests\Unit\Db;

use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Db\TeamFavoriteRequest;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\TestCase;

class TeamFavoriteRequestTest extends TestCase {
	private IDBConnection $db;
	private TeamFavoriteRequest $request;
	private string $userId;
	private string $first;
	private string $second;
	private string $third;

	protected function setUp(): void {
		$this->db = Server::get(IDBConnection::class);
		$this->request = Server::get(TeamFavoriteRequest::class);
		$this->userId = 'favorite-test-' . bin2hex(random_bytes(8));
		$this->first = bin2hex(random_bytes(7));
		$this->second = bin2hex(random_bytes(7));
		$this->third = bin2hex(random_bytes(7));
		$this->db->beginTransaction();
	}

	protected function tearDown(): void {
		$this->db->rollBack();
	}

	public function testAppendAfterCircleDeletionKeepsOrder(): void {
		$this->request->add($this->userId, $this->first);
		$this->request->add($this->userId, $this->second);
		$this->request->removeCircle($this->first);
		$this->request->add($this->userId, $this->third);
		self::assertSame([$this->second, $this->third], $this->request->getCircleIds($this->userId));
	}

	public function testReorderPreservesRowsAndDoesNotDeleteNewFavorites(): void {
		$this->request->add($this->userId, $this->first);
		$this->request->add($this->userId, $this->second);
		$before = $this->rows();
		$this->request->add($this->userId, $this->third);
		$this->request->replaceOrder($this->userId, [$this->second, $this->first]);
		self::assertSame([$this->second, $this->first, $this->third], $this->request->getCircleIds($this->userId));
		$after = $this->rows();
		foreach ($before as $index => $row) {
			self::assertSame($row, $after[$index]);
		}
	}

	public function testReorderCannotResurrectRemovedFavorites(): void {
		$this->request->add($this->userId, $this->first);
		$this->request->add($this->userId, $this->second);
		$this->request->remove($this->userId, $this->first);
		$this->request->replaceOrder($this->userId, [$this->second, $this->first]);
		self::assertSame([$this->second], $this->request->getCircleIds($this->userId));
	}

	public function testReadIncludesDirectAndInheritedMembershipButNotLostAccess(): void {
		$singleId = bin2hex(random_bytes(7));
		$groupId = bin2hex(random_bytes(7));
		$memberRequest = Server::get(MemberRequest::class);
		$memberRequest->save($this->member($this->first, $singleId, $this->userId, Member::TYPE_USER));
		$memberRequest->save($this->member($this->second, $groupId, 'favorite-test-group', Member::TYPE_GROUP));
		$membership = new Membership();
		$membership->setCircleId($this->second);
		$membership->setSingleId($singleId);
		$membership->setLevel(Member::LEVEL_MEMBER);
		$membership->setInheritanceFirst($groupId);
		$membership->setInheritanceLast($groupId);
		$membership->setInheritancePath([$groupId]);
		$membership->setInheritanceDepth(1);
		$memberships = Server::get(MembershipRequest::class);
		$memberships->insert($membership);
		foreach ([$this->first, $this->second, $this->third] as $circleId) {
			$this->request->add($this->userId, $circleId);
		}
		self::assertSame([$this->first, $this->second], $this->request->getCircleIds($this->userId, true));
		$memberships->delete($membership);
		self::assertSame([$this->first], $this->request->getCircleIds($this->userId, true));
	}

	public function testUserCleanupDoesNotAffectOtherUsers(): void {
		$this->request->add($this->userId, $this->first);
		$this->request->add($this->userId . '-other', $this->first);
		$this->request->removeUser($this->userId);
		self::assertSame([], $this->request->getCircleIds($this->userId));
		self::assertSame([$this->first], $this->request->getCircleIds($this->userId . '-other'));
	}

	private function member(string $circleId, string $singleId, string $userId, int $type): Member {
		$member = new Member();
		$member->setCircleId($circleId);
		$member->setSingleId($singleId);
		$member->setId(bin2hex(random_bytes(7)));
		$member->setUserId($userId);
		$member->setUserType($type);
		$member->setLevel(Member::LEVEL_MEMBER);
		$member->setStatus(Member::STATUS_MEMBER);
		return $member;
	}

	private function rows(): array {
		$query = $this->db->getQueryBuilder();
		$query->select('id', 'circle_id', 'created')->from(CoreRequestBuilder::TABLE_TEAM_FAVORITES)
			->where($query->expr()->eq('user_id', $query->createNamedParameter($this->userId)))
			->orderBy('id');
		$result = $query->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		return $rows;
	}
}
