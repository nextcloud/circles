<?php


namespace OCA\Circles\Activity;


use Exception;
use InvalidArgumentException;
use OCA\Circles\Model\Circle;
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
	 * @since 11.0.0
	 */
	public function parse($lang, IEvent $event, IEvent $previousEvent = null) {

		if ($event->getApp() !== 'circles') {
			throw new \InvalidArgumentException();
		}

		$event = $this->parseCreation($lang, $event);
		$event = $this->parseInvitation($lang, $event);
		$event = $this->parsePopulation($lang, $event);
		$event = $this->parseRights($lang, $event);
		$event = $this->parseShares($lang, $event);

		return $event;
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 * @throws Exception
	 */
	private function parseCreation($lang, IEvent $event) {
		if ($event->getType() !== 'circles_creation') {
			return $event;
		}

		$params = $event->getSubjectParameters();
		$circle = Circle::fromJSON($this->l10n, $params['circle']);
		if ($circle === null) {
			throw new \InvalidArgumentException();
		}
		$event->setIcon(CirclesService::getCircleIcon($circle->getType()));

		switch ($event->getSubject()) {
			case 'create':
				return $this->parseCreationCreate($lang, $circle, $event);

			case 'delete':
				return $this->parseCreationDelete($lang, $event);

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
	private function parseCreationCreate($lang, Circle $circle, IEvent $event) {

	$this->miscService->log("____" . $this->activityManager->getCurrentUserId() . '   ' . $circle->getOwner()->getUserId());
		if ($circle->getOwner()
				   ->getUserId() === $this->activityManager->getCurrentUserId()
		) {

			$event->setRichSubject(
				$this->l10n->t(
					'You created the circle {circles}'
				),
				['circles' => $this->generateCircleParameter($circle)]
			);

		} else {

			$author = $this->generateUserParameter(
				$circle->getOwner()
					   ->getUserId()
			);
			$event->setRichSubject(
					  $this->l10n->t(
						  '{author} created the circle {circles}'
					  ), [
						  'author'  => $author,
						  'circles' => $this->generateCircleParameter($circle)
					  ]
				  );
		}


		return $event;
//		throw new InvalidArgumentException();
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseCreationDelete($lang, IEvent $event) {
		return $event;
//		throw new InvalidArgumentException();
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseInvitation($lang, IEvent $event) {
		if ($event->getType() !== 'circles_invitation') {
			return $event;
		}

		return $event;
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parsePopulation($lang, IEvent $event) {
		if ($event->getType() !== 'circles_population') {
			return $event;
		}

		return $event;
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseRights($lang, IEvent $event) {
		if ($event->getType() !== 'circles_rights') {
			return $event;
		}

		return $event;
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseShares($lang, IEvent $event) {
		if ($event->getType() !== 'circles_shares') {
			return $event;
		}

		return $event;
	}

//	private function parseMood(IEvent &$event, $mood) {
//
//		if (key_exists('website', $mood)) {
//			$event->setRichMessage(
//				$mood['text'] . '{opengraph}',
//				['opengraph' => $this->generateOpenGraphParameter('_id_', $mood['website'])]
//			);
//		} else {
//			$event->setParsedMessage($mood['text']);
//		}
//
//	}


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


	private function generateCircleParameter(Circle $circle) {
		return [
			'type' => 'circle',
			'id'   => $circle->getId(),
			'name' => $circle->getName(),
			'link' => \OC::$server->getURLGenerator()
								  ->linkToRoute('circles.Navigation.navigate')
					  . '#' . $circle->getId()
		];
	}


	/**
	 * @param $userId
	 *
	 * @return array
	 */
	private function generateUserParameter($userId) {
			return [
			'type' => 'user',
			'id'   => $userId,
			'name' => $userId
		];
	}
}
