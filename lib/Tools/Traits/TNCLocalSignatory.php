<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Model\NCSignatory;
use OCP\IConfig;
use OCP\Server;

trait TNCLocalSignatory {
	use TNCSignatory;

	public static $SIGNATORIES_APP = 'signatories';


	/**
	 * @param NCSignatory $signatory
	 * @param bool $generate
	 *
	 * @throws SignatoryException
	 */
	public function fillSimpleSignatory(NCSignatory $signatory, bool $generate = false): void {
		$app = $this->setup('app', '', self::$SIGNATORIES_APP);
		$signatories = json_decode(Server::get(IConfig::class)->getAppValue($app, 'key_pairs'), true);
		if (!is_array($signatories)) {
			$signatories = [];
		}

		$sign = $this->getArray($signatory->getId(), $signatories);
		if (!empty($sign)) {
			$signatory->setKeyId($this->get('keyId', $sign))
				->setKeyOwner($this->get('keyOwner', $sign))
				->setPublicKey($this->get('publicKey', $sign))
				->setPrivateKey($this->get('privateKey', $sign));

			return;
		}

		if (!$generate) {
			throw new SignatoryException('signatory not found');
		}

		$this->createSimpleSignatory($signatory);
	}


	/**
	 * @param NCSignatory $signatory
	 */
	public function createSimpleSignatory(NCSignatory $signatory): void {
		$app = $this->setup('app', '', self::$SIGNATORIES_APP);
		$signatory->setKeyId($signatory->getId() . '#main-key');
		$signatory->setKeyOwner($signatory->getId());
		$this->generateKeys($signatory);

		$signatories =
			json_decode(Server::get(IConfig::class)->getAppValue($app, 'key_pairs', '[]'), true);
		$signatories[$signatory->getId()] = [
			'keyId' => $signatory->getKeyId(),
			'keyOwner' => $signatory->getKeyOwner(),
			'publicKey' => $signatory->getPublicKey(),
			'privateKey' => $signatory->getPrivateKey()
		];

		Server::get(IConfig::class)->setAppValue($app, 'key_pairs', json_encode($signatories));
	}


	/**
	 * @param NCSignatory $signatory
	 */
	public function removeSimpleSignatory(NCSignatory $signatory): void {
		$app = $this->setup('app', '', self::$SIGNATORIES_APP);
		$signatories = json_decode(Server::get(IConfig::class)->getAppValue($app, 'key_pairs'), true);
		if (!is_array($signatories)) {
			$signatories = [];
		}

		unset($signatories[$signatory->getId()]);
		Server::get(IConfig::class)->setAppValue($app, 'key_pairs', json_encode($signatories));
	}
}
