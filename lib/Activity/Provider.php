<?php


namespace OCA\Circles\Activity;


use Exception;
use InvalidArgumentException;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MiscService;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OpenCloud\Common\Exceptions\InvalidArgumentError;


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

		$params = $event->getSubjectParameters();
		$circle = Circle::fromJSON($this->l10n, $params['circle']);
		$event->setIcon(CirclesService::getCircleIcon($circle->getType()));

		$event = $this->parseAsMember($circle, $event, $params);
		$event = $this->parseAsModerator($circle, $event, $params);

		$this->generateParsedSubject($event);

		return $event;
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @return IEvent
	 */
	private function parseAsMember(Circle $circle, IEvent $event, $params) {
		if ($event->getType() !== 'circles_as_member') {
			return $event;
		}

		switch ($event->getSubject()) {
			case 'circle_create':
				return $this->parseCircleEvent(
					$circle, null, $event,
					$this->l10n->t('You created the circle {circle}'),
					$this->l10n->t('{author} created the circle {circle}')
				);

			case 'circle_delete':
				return $this->parseCircleEvent(
					$circle, null, $event,
					$this->l10n->t('You deleted {circle}'),
					$this->l10n->t('{author} deleted {circle}')
				);
		}

		if (key_exists('member', $params)) {
			$event = $this->parseMemberAsMember($circle, $event);
		}

		return $event;
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberAsMember(Circle $circle, IEvent $event) {
		$params = $event->getSubjectParameters();
		$member = Member::fromJSON($this->l10n, $params['member']);

		switch ($event->getSubject()) {
			case 'member_join':
				return $this->parseCircleMemberEvent(
					$circle, $member, $event,
					$this->l10n->t('You joined {circle}'),
					$this->l10n->t('{member} joined {circle}')
				);

			case 'member_add':
				return $this->parseCircleMemberAdvancedEvent(
					$circle, $member, $event,
					$this->l10n->t('You added {member} as member to {circle}'),
					$this->l10n->t('You were added as member to {circle} by {author}'),
					$this->l10n->t('{member} was added as member to {circle} by {author}')
				);

			case 'member_left':
				return $this->parseCircleMemberEvent(
					$circle, $member, $event,
					$this->l10n->t('You left {circle}'),
					$this->l10n->t('{member} left {circle}')
				);

			case 'member_remove':
				return $this->parseCircleMemberAdvancedEvent(
					$circle, $member, $event,
					$this->l10n->t('You removed {member} from {circle}'),
					$this->l10n->t('You were removed from {circle} by {author}'),
					$this->l10n->t('{member} was removed from {circle} by {author}')
				);
		}

		return $event;
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @return IEvent
	 * @throws Exception
	 */
	private function parseAsModerator(Circle $circle, IEvent $event, $params) {
		if ($event->getType() !== 'circles_as_moderator') {
			return $event;
		}

		try {
			if (key_exists('member', $params)) {
				return $this->parseMemberAsModerator($circle, $event);
			}

			if (key_exists('link', $params)) {
				return $this->parseLinkAsModerator($circle, $event);
			}

			throw new InvalidArgumentError();
		} catch (Exception $e) {
			throw $e;
		}

	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberAsModerator(Circle $circle, IEvent $event) {

		$params = $event->getSubjectParameters();
		$member = Member::fromJSON($this->l10n, $params['member']);

		switch ($event->getSubject()) {
			case 'member_invited':
				return $this->parseCircleMemberAdvancedEvent(
					$circle, $member, $event,
					$this->l10n->t('You invited {member} into {circle}'),
					$this->l10n->t('You have been invited into {circle} by {author}'),
					$this->l10n->t('{member} have been invited into {circle} by {author}')
				);

			case 'member_level':
				$level = [$this->l10n->t($member->getLevelString())];

				return $this->parseCircleMemberAdvancedEvent(
					$circle, $member, $event,
					$this->l10n->t('You changed {member}\'s level in {circle} to %1$s', $level),
					$this->l10n->t('{author} changed your level in {circle} to %1$s', $level),
					$this->l10n->t('{author} changed {member}\'s level in {circle} to %1$s', $level)
				);

			case 'member_request_invitation':
				return $this->parseMemberEvent(
					$circle, $member, $event,
					$this->l10n->t('You requested an invitation to {circle}'),
					$this->l10n->t(
						'{member} has requested an invitation into {circle}'
					)
				);

			case 'member_owner':
				return $this->parseMemberEvent(
					$circle, $member, $event,
					$this->l10n->t('You are the new owner of {circle}'),
					$this->l10n->t('{member} is the new owner of {circle}')
				);
		}

		throw new InvalidArgumentException();
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseLinkAsModerator(Circle $circle, IEvent $event) {

		$params = $event->getSubjectParameters();
		$link = FederatedLink::fromJSON($params['link']);

		switch ($event->getSubject()) {
			case 'link_request_sent':
				return $this->parseCircleEvent(
					$circle, $link, $event,
					$this->l10n->t('You sent a request to link {circle} with {link}'),
					$this->l10n->t('{author} sent a request to link {circle} with {link}')
				);

			case 'link_request_received';
				return $this->parseLinkEvent(
					$circle, $link, $event,
					$this->l10n->t('{link} requested a link with {circle}')
				);

			case 'link_request_rejected';
				return $this->parseLinkEvent(
					$circle, $link, $event, $this->l10n->t(
					'The request to link {circle} with {link} has been rejected'
				)
				);

			case 'link_request_canceled':
				return $this->parseLinkEvent(
					$circle, $link, $event,
					$this->l10n->t(
						'The request to link {link} with {circle} has been canceled remotely'
					)
				);

			case 'link_request_accepted':
				return $this->parseLinkEvent(
					$circle, $link, $event,
					$this->l10n->t('The request to link {circle} with {link} has been accepted')
				);

			case 'link_request_removed':
				return $this->parseCircleEvent(
					$circle, $link, $event,
					$this->l10n->t('You dismissed the request to link {link} with {circle}'),
					$this->l10n->t('{author} dismissed the request to link {link} with {circle}')
				);

			case 'link_request_canceling':
				return $this->parseCircleEvent(
					$circle, $link, $event,
					$this->l10n->t('You canceled the request to link {circle} with {link}'),
					$this->l10n->t('{author} canceled the request to link {circle} with {link}')
				);

			case 'link_request_accepting':
				return $this->parseCircleEvent(
					$circle, $link, $event,
					$this->l10n->t('You accepted the request to link {link} with {circle}'),
					$this->l10n->t('{author} accepted the request to link {link} with {circle}')
				);

			case 'link_up':
				return $this->parseLinkEvent(
					$circle, $link, $event,
					$this->l10n->t('A link between {circle} and {link} is now up and running')
				);

			case 'link_down':
				return $this->parseLinkEvent(
					$circle, $link, $event,
					$this->l10n->t(
						'The link between {circle} and {link} has been shutdown remotely'
					)
				);

			case 'link_remove':
				return $this->parseCircleEvent(
					$circle, $link, $event,
					$this->l10n->t('You closed the link between {circle} and {link}'),
					$this->l10n->t('{author} closed the link between {circle} and {link}')
				);
		}

		throw new InvalidArgumentException();
	}


	/**
	 * general function to generate Circle event.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 * @param IEvent $event
	 * @param $ownEvent
	 * @param $othersEvent
	 *
	 * @return IEvent
	 */
	private function parseCircleEvent(Circle $circle, $link, IEvent $event, $ownEvent, $othersEvent
	) {
		$data = [
			'author' => $author = $this->generateUserParameter(
				$circle->getUser()
					   ->getUserId()
			),
			'circle' => $this->generateCircleParameter($circle),
			'link'   => ($link === null) ? '' : $this->generateLinkParameter($link)
		];

		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			return $event->setRichSubject($ownEvent, $data);
		}

		return $event->setRichSubject($othersEvent, $data);
	}


	/**
	 * general function to generate Member event.
	 *
	 * @param Circle $circle
	 * @param $member
	 * @param IEvent $event
	 * @param $ownEvent
	 * @param $othersEvent
	 *
	 * @return IEvent
	 */
	private function parseMemberEvent(
		Circle $circle, Member $member, IEvent $event, $ownEvent, $othersEvent
	) {
		$data = [
			'circle' => $this->generateCircleParameter($circle),
			'member' => $this->generateMemberParameter($member)
		];

		if ($member->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			return $event->setRichSubject($ownEvent, $data);
		}

		return $event->setRichSubject($othersEvent, $data);
	}


	/**
	 * general function to generate Link event.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 * @param IEvent $event
	 * @param $line
	 *
	 * @return IEvent
	 */
	private function parseLinkEvent(Circle $circle, FederatedLink $link, IEvent $event, $line) {
		$data = [
			'circle' => $this->generateCircleParameter($circle),
			'link'   => $this->generateLinkParameter($link)
		];

		return $event->setRichSubject($line, $data);
	}


	/**
	 * general function to generate Circle+Member event.
	 *
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 * @param $ownEvent
	 * @param $othersEvent
	 *
	 * @return IEvent
	 */
	private function parseCircleMemberEvent(
		Circle $circle, Member $member, IEvent $event, $ownEvent, $othersEvent
	) {
		$data = [
			'circle' => $this->generateCircleParameter($circle),
			'member' => $this->generateMemberParameter($member)
		];

		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			return $event->setRichSubject($ownEvent, $data);
		}

		return $event->setRichSubject($othersEvent, $data);
	}


	/**
	 * general function to generate Circle+Member advanced event.
	 *
	 * @param Circle $circle
	 * @param Member $member
	 * @param IEvent $event
	 *\
	 * @param $ownEvent
	 * @param $targetEvent
	 * @param $othersEvent
	 *
	 * @return IEvent
	 */
	private function parseCircleMemberAdvancedEvent(
		Circle $circle, Member $member, IEvent $event, $ownEvent, $targetEvent, $othersEvent
	) {

		$data = [
			'author' => $this->generateUserParameter(
				$circle->getUser()
					   ->getUserId()
			),
			'circle' => $this->generateCircleParameter($circle),
			'member' => $this->generateMemberParameter($member)
		];

		if ($circle->getUser()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			return $event->setRichSubject($ownEvent, $data);
		}

		if ($member->getUserId() === $this->activityManager->getCurrentUserId()) {
			return $event->setRichSubject($targetEvent, $data);
		}

		return $event->setRichSubject($othersEvent, $data);
	}


	/**
	 * @param IEvent $event
	 */
	private function generateParsedSubject(IEvent $event) {
		$subject = $event->getRichSubject();
		$params = $event->getRichSubjectParameters();
		$ak = array_keys($params);
		foreach ($ak as $k) {
			$subject = str_replace('{' . $k . '}', $params[$k]['parsed'], $subject);
		}

		$event->setParsedSubject($subject);
	}

	/**
	 * @param Member $member
	 *
	 * @return array<string,string|integer>
	 */
	private function generateMemberParameter(Member $member) {
		return $this->generateUserParameter($member->getUserId());
	}


	/**
	 * @param Circle $circle
	 *
	 * @return array<string,string|integer>
	 */
	private function generateCircleParameter(Circle $circle) {
		return [
			'type'   => 'circle',
			'id'     => $circle->getId(),
			'name'   => $circle->getName(),
			'parsed' => $circle->getName(),
			'link'   => Circles::generateLink($circle->getId())
		];
	}


	/**
	 * @param FederatedLink $link
	 *
	 * @return array<string,string|integer>
	 */
	private function generateLinkParameter(FederatedLink $link) {
		return [
			'type'   => 'circle',
			'id'     => $link->getUniqueId(),
			'name'   => $link->getToken() . '@' . $link->getAddress(),
			'parsed' => $link->getToken() . '@' . $link->getAddress()
		];
//			'link' => Circles::generateRemoteLink($link)
	}


	/**
	 * @param $userId
	 *
	 * @return array<string,string|integer>
	 */
	private function generateUserParameter($userId) {
		return [
			'type'   => 'user',
			'id'     => $userId,
			'name'   => \OC::$server->getUserManager()
									->get($userId)
									->getDisplayName(),
			'parsed' => \OC::$server->getUserManager()
									->get($userId)
									->getDisplayName()
		];
	}
}
