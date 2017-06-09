<?php


namespace OCA\Circles\Activity;


use Exception;
use InvalidArgumentException;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharingFrame;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MiscService;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;


class Provider implements IProvider {

	/** @var MiscService */
	protected $miscService;

	/** @var IL10N */
	protected $l10n;

	/** @var IURLGenerator */
	protected $url;

	/** @var IManager */
	protected $activityManager;

	public function __construct(
		IURLGenerator $url, IManager $activityManager, IL10N $l10n, MiscService $miscService
	) {
		$this->url = $url;
		$this->activityManager = $activityManager;
		$this->l10n = $l10n;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 *
	 * @return IEvent
	 */
	public function parse($lang, IEvent $event, IEvent $previousEvent = null) {

		if ($event->getApp() !== 'circles') {
			throw new \InvalidArgumentException();
		}

		$event = $this->parseAsMember($lang, $event);
		$event = $this->parseAsModerator($lang, $event);

		return $event;
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 * @throws Exception
	 */
	private function parseAsMember($lang, IEvent $event) {
		if ($event->getType() !== 'circles_as_member') {
			return $event;
		}

		$params = $event->getSubjectParameters();
		$circle = Circle::fromJSON($this->l10n, $params['circle']);
		if ($circle === null) {
			return $event;
		}

		$event->setIcon(CirclesService::getCircleIcon($circle->getType()));
		switch ($event->getSubject()) {
			case 'circle_create':
				return $this->parseCircleCreate($lang, $circle, $event);

			case 'circle_delete':
				return $this->parseCircleDelete($lang, $circle, $event);
		}

		$event = $this->parseMembersAsMember($lang, $circle, $event);

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMembersAsMember($lang, Circle $circle, IEvent $event) {
		$params = $event->getSubjectParameters();

		$member = Member::fromJSON($this->l10n, $params['member']);
		if ($member === null) {
			return $event;
		}

		switch ($event->getSubject()) {
			case 'member_join':
				return $this->parseMemberJoin($lang, $circle, $member, $event);

			case 'member_add':
				return $this->parseMemberAdd($lang, $circle, $member, $event);

			case 'member_left':
				return $this->parseMemberLeft($lang, $circle, $member, $event);

			case 'member_remove':
				return $this->parseMemberRemove($lang, $circle, $member, $event);

			default:
				return $event;
		}

	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseAsModerator($lang, IEvent $event) {
		if ($event->getType() !== 'circles_as_moderator') {
			return $event;
		}

		$params = $event->getSubjectParameters();
		$circle = Circle::fromJSON($this->l10n, $params['circle']);
		$member = Member::fromJSON($this->l10n, $params['member']);
		if ($member === null || $circle === null) {
			return $event;
		}

		$event->setIcon(CirclesService::getCircleIcon($circle->getType()));

		switch ($event->getSubject()) {
			case 'member_invited':
				return $this->parseMemberInvited($lang, $circle, $member, $event);

			case 'member_request_invitation':
				return $this->parseMemberRequestInvitation($lang, $circle, $member, $event);

			case 'member_level':
				return $this->parseMemberLevel($lang, $circle, $member, $event);

			case 'member_owner':
				return $this->parseMemberOwner($lang, $circle, $member, $event);

			default:
				throw new InvalidArgumentException();
		}
	}

	/**
	 * @param string $lang
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseCircleCreate($lang, Circle $circle, IEvent $event) {
		if ($circle->getOwner()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You created the circle {circle}'),
				['circle' => $this->generateCircleParameter($circle)]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t('{author} created the circle {circle}'),
				[
					'author' => $author = $this->generateUserParameter(
						$circle->getOwner()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle)
				]
			);
		}

		return $event;
	}


	/**
	 * @param string $lang
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseCircleDelete($lang, Circle $circle, IEvent $event) {
		if ($circle->getOwner()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You deleted {circle}'),
				['circle' => $this->generateCircleParameter($circle)]
			);
		} else {
			$event->setRichSubject(
				$this->l10n->t('{author} deleted {circle}'),
				[
					'author' => $this->generateUserParameter(
						$circle->getOwner()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle)
				]
			);
		}

		return $event;
	}


	/**
	 * @param string $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberInvited($lang, Circle $circle, Member $member, IEvent $event) {

		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You invited {member} into {circle}'),
				[
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);

		} elseif ($member->getUserId() === $this->activityManager->getCurrentUserId()) {
			$event->setRichSubject(
				$this->l10n->t('You have been invited into {circle} by {author}'),
				[
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle)
				]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t('{member} have been invited into {circle} by {author}'),
				[
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);
		}

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberRequestInvitation(
		$lang, Circle $circle, Member $member, IEvent $event
	) {
		if ($member->getUserId() === $this->activityManager->getCurrentUserId()) {
			$event->setRichSubject(
				$this->l10n->t('You requested an invitation to {circle}'),
				['circle' => $this->generateCircleParameter($circle)]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t(
					'{author} has requested an invitation into {circle}'
				), [
					'author' => $this->generateMemberParameter($member),
					'circle' => $this->generateCircleParameter($circle)
				]
			);
		}

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberJoin($lang, Circle $circle, Member $member, IEvent $event) {
		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You joined {circle}'),
				['circle' => $this->generateCircleParameter($circle)]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t(
					'{member} has joined the circle {circle}'
				), [
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);
		}

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberAdd($lang, Circle $circle, Member $member, IEvent $event) {

		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You added {member} as member to {circle}'),
				[
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);

		} elseif ($member->getUserId() === $this->activityManager->getCurrentUserId()) {
			$event->setRichSubject(
				$this->l10n->t('You were added as member to {circle} by {author}'),
				[
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle)
				]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t(
					'{member} was added as member to {circle} by {author}'
				), [
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);
		}

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberLeft($lang, Circle $circle, Member $member, IEvent $event) {
		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You left {circle}'),
				['circle' => $this->generateCircleParameter($circle)]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t(
					'{member} has left {circle}'
				), [
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);
		}

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberRemove($lang, Circle $circle, Member $member, IEvent $event) {

		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You removed {member} from {circle}'),
				[
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);

		} elseif ($member->getUserId() === $this->activityManager->getCurrentUserId()) {
			$event->setRichSubject(
				$this->l10n->t('You were removed from {circle} by {author}'),
				[
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle)
				]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t(
					'{member} was removed from {circle} by {author}'
				), [
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);
		}

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberLevel($lang, Circle $circle, Member $member, IEvent $event) {

		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t(
					'You changed {member}\'s level in {circle} to %1$s',
					[$this->l10n->t($member->getLevelString())]
				),
				[
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);

		} elseif ($member->getUserId() === $this->activityManager->getCurrentUserId()) {
			$event->setRichSubject(
				$this->l10n->t(
					'{author} changed your level in {circle} to %1$s',
					[$this->l10n->t($member->getLevelString())]
				),
				[
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle),
					'level'  => $this->l10n->t($member->getLevelString())
				]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t(
					'{author} changed {member}\'s level in {circle} to %1$s',
					[$this->l10n->t($member->getLevelString())]
				), [
					'author' => $this->generateUserParameter(
						$circle->getUser()
							   ->getUserId()
					),
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member),
					'level'  => $this->l10n->t($member->getLevelString())
				]
			);
		}

		return $event;
	}


	/**
	 * @param $lang
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberOwner($lang, Circle $circle, Member $member, IEvent $event) {
		if ($member->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$event->setRichSubject(
				$this->l10n->t('You are the new owner of {circle}'),
				['circle' => $this->generateCircleParameter($circle)]
			);

		} else {
			$event->setRichSubject(
				$this->l10n->t(
					'{member} is the new owner of {circle}'
				), [
					'circle' => $this->generateCircleParameter($circle),
					'member' => $this->generateMemberParameter($member)
				]
			);
		}

		return $event;
	}

	private function parseActivityHeader(IEvent &$event, SharingFrame $frame) {

		$this->activityManager->getCurrentUserId();

		if ($frame->getAuthor() === $this->activityManager->getCurrentUserId()
			&& $frame->getCloudId() === null
		) {

			$event->setParsedSubject(
				$this->l10n->t(
					'You shared a mood with %1$s', ['circle1, circle2']
				)
			)
				  ->setRichSubject(
					  $this->l10n->t(
						  'You shared a mood with {circles}'
					  ),
					  ['circles' => $this->generateCircleParameter($frame)]

				  );

		} else {

			$author = $this->generateUserParameter($frame);
			$event->setParsedSubject(
				$this->l10n->t(
					'%1$s shared a mood with %2$s', [
													  $author['name'],
													  'circle1, circle2'
												  ]
				)
			)
				  ->setRichSubject(
					  $this->l10n->t(
						  '{author} shared a mood with {circles}'
					  ), [
						  'author'  => $author,
						  'circles' => $this->generateCircleParameter($frame)
					  ]
				  );
		}
	}


	private function generateMemberParameter(
		Member $member
	) {
		return $this->generateUserParameter($member->getUserId());
	}


	private function generateCircleParameter(
		Circle $circle
	) {
		return [
			'type' => 'circle',
			'id'   => $circle->getId(),
			'name' => $circle->getName(),
			'link' => Circles::generateLink($circle->getId())
		];
	}

	/**
	 * @param $userId
	 *
	 * @return array
	 */
	private function generateUserParameter(
		$userId
	) {
		return [
			'type' => 'user',
			'id'   => $userId,
			'name' => $userId
		];
	}
}
