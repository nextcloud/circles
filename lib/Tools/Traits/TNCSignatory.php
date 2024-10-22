<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Tools\Traits;

use OCA\Circles\Tools\Exceptions\InvalidOriginException;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Exceptions\SignatureException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\NCSignatory;

trait TNCSignatory {
	use TNCRequest;


	/**
	 * return Signatory by its Id from cache or from direct request.
	 * Should be overwritten.
	 *
	 * @param string $keyId
	 * @param bool $refresh
	 *
	 * @return NCSignatory
	 * @throws SignatoryException
	 */
	public function retrieveSignatory(string $keyId, bool $refresh = false): NCSignatory {
		if (!$refresh) {
			throw new SignatoryException();
		}

		$signatory = new NCSignatory($keyId);
		$this->downloadSignatory($signatory, $keyId);

		return $signatory;
	}


	/**
	 * @param NCSignatory $signatory
	 * @param string $keyId
	 * @param array $params
	 * @param NCRequest|null $request
	 *
	 * @throws SignatoryException
	 */
	public function downloadSignatory(
		NCSignatory $signatory,
		string $keyId = '',
		array $params = [],
		?NCRequest $request = null,
	): void {
		if (is_null($request)) {
			$request = new NCRequest();
			$request->setFollowLocation(true);
			$request->setTimeout(5);
		}

		$request->basedOnUrl(($keyId !== '') ? $keyId : $signatory->getId());
		$request->setParams($params);
		$request->addHeader('Accept', 'application/ld+json');

		try {
			$this->updateSignatory($signatory, $this->retrieveJson($request), $keyId);
		} catch (RequestNetworkException $e) {
			$this->debug('network issue while downloading Signatory', ['request' => $request]);
			throw new SignatoryException('network issue: ' . $e->getMessage());
		}
	}


	/**
	 * @param NCSignatory $signatory
	 * @param array $json
	 * @param string $keyId
	 *
	 * @throws SignatoryException
	 */
	public function updateSignatory(NCSignatory $signatory, array $json, string $keyId = ''): void {
		$signatory->setOrigData($json)
			->import($json);

		if ($keyId === '') {
			$keyId = $signatory->getKeyId();
		}

		try {
			if (($signatory->getId() !== $keyId && $signatory->getKeyId() !== $keyId)
				|| $signatory->getId() !== $signatory->getKeyOwner()
				|| $this->getKeyOrigin($signatory->getKeyId()) !== $this->getKeyOrigin($signatory->getId())
				|| $signatory->getPublicKey() === '') {
				$this->debug('invalid format', ['signatory' => $signatory, 'keyId' => $keyId]);
				throw new SignatoryException('invalid format');
			}
		} catch (InvalidOriginException $e) {
			throw new SignatoryException('invalid origin');
		}
	}


	/**
	 * @param string $keyId
	 *
	 * @return string
	 * @throws InvalidOriginException
	 */
	public function getKeyOrigin(string $keyId) {
		$host = parse_url($keyId, PHP_URL_HOST);
		if (is_string($host) && ($host !== '')) {
			return $host;
		}

		throw new InvalidOriginException('cannot retrieve origin from ' . $keyId);
	}


	/**
	 * @param NCSignatory $signatory
	 * @param string $digest
	 * @param int $bits
	 * @param int $type
	 */
	public function generateKeys(
		NCSignatory $signatory,
		string $digest = 'rsa',
		int $bits = 2048,
		int $type = OPENSSL_KEYTYPE_RSA,
	) {
		$res = openssl_pkey_new(
			[
				'digest_alg' => $digest,
				'private_key_bits' => $bits,
				'private_key_type' => $type,
			]
		);

		openssl_pkey_export($res, $privateKey);
		$publicKey = openssl_pkey_get_details($res)['key'];

		$signatory->setPublicKey($publicKey);
		$signatory->setPrivateKey($privateKey);
	}


	/**
	 * @param string $clear
	 * @param NCSignatory $signatory
	 *
	 * @return string
	 * @throws SignatoryException
	 */
	public function signString(string $clear, NCSignatory $signatory): string {
		$privateKey = $signatory->getPrivateKey();
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		openssl_sign($clear, $signed, $privateKey, $this->getOpenSSLAlgo($signatory));

		return base64_encode($signed);
	}


	/**
	 * @param string $clear
	 * @param string $signed
	 * @param string $publicKey
	 * @param string $algo
	 *
	 * @throws SignatureException
	 */
	public function verifyString(
		string $clear, string $signed, string $publicKey, string $algo = NCSignatory::SHA256,
	) {
		if (openssl_verify($clear, $signed, $publicKey, $algo) !== 1) {
			throw new SignatureException('signature issue');
		}
	}
}
