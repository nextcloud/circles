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

		switch ($event->getSubject()) {
			case 'create':
				return $this->parseCreationCreate($lang, $event);

			case 'delete':
				return $this->parseCreationDelete($lang, $event);

			default:
				throw new InvalidArgumentException();
		}
	}


	/**
	 * @param string $lang
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseCreationCreate($lang, IEvent $event) {

		$params = $event->getSubjectParameters();
		$this->miscService->log(var_export($params, true));

		$circle = Circle::fromJSON($this->l10n, $params['circle']);
        $event->setIcon(CirclesService::getCircleIcon($circle->getType()));

//          $this->url->getAbsoluteURL($this->url->imagePath('circles', 'black_circle.svg'))
//        );

//        $frame = SharingFrame::fromJSON($params['share']);
//
//        if ($frame === null) {
//          throw new \InvalidArgumentException();
//        }
//        $mood = $frame->getPayload();
//        $this->parseActivityHeader($event, $frame);
//        $this->parseMood($event, $mood);
//        break;
//
//      default:
//        throw new \InvalidArgumentException();
//    }


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


	private function generateOpenGraphParameter($id, $website) {
		return [
			'type'        => 'open-graph',
			'id'          => $id,
			'name'        => $website['title'],
			'description' => $website['description'],
			'website'     => $website['website'],
			'thumb'       => \OC::$server->getURLGenerator()
										 ->linkToRoute('mood.Tools.binFromExternalImage') . '?url='
							 . rawurlencode($website['thumb']),
			'link'        => $website['url']
		];
	}


	private function generateCircleParameter(SharingFrame $frame) {
		return [
			'type' => 'circle',
			'id'   => $frame->getCircleId(),
			'name' => $frame->getCircleName(),
			'link' => \OC::$server->getURLGenerator()
								  ->linkToRoute('circles.Navigation.navigate')
					  . '#' . $frame->getCircleId()
		];
	}


	/**
	 * @param SharingFrame $frame
	 *
	 * @return array
	 */
	private function generateUserParameter(SharingFrame $frame) {
		$host = '';
		if ($frame->getCloudId() !== null) {
			$host = '@' . $frame->getCloudId();
		}

		return [
			'type' => 'user',
			'id'   => $frame->getAuthor(),
			'name' => $frame->getAuthor() . $host
		];
	}
}
