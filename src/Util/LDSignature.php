<?php

namespace Friendica\Util;

use Friendica\Util\JsonLD;
use Friendica\Util\DateTimeFormat;
use Friendica\Protocol\ActivityPub;
use Friendica\Model\APContact;

/**
 * @brief Implements JSON-LD signatures
 *
 * Ported from Osada: https://framagit.org/macgirvin/osada
 */
class LDSignature
{
	public static function isSigned($data)
	{
		return !empty($data['signature']);
	}

	public static function getSigner($data)
	{
		if (!self::isSigned($data)) {
			return false;
		}

		$actor = JsonLD::fetchElement($data, 'actor', 'id');
		if (empty($actor)) {
			return false;
		}

		$profile = APContact::getProfileByURL($actor);
		if (empty($profile['pubkey'])) {
			return false;
		}
		$pubkey = $profile['pubkey'];

		$ohash = self::hash(self::signable_options($data['signature']));
		$dhash = self::hash(self::signable_data($data));

		$x = Crypto::rsaVerify($ohash . $dhash, base64_decode($data['signature']['signatureValue']), $pubkey);
		logger('LD-verify: ' . intval($x));

		if (empty($x)) {
			return false;
		} else {
			return $actor;
		}
	}

	public static function sign($data, $owner)
	{
		$options = [
			'type' => 'RsaSignature2017',
			'nonce' => random_string(64),
			'creator' => $owner['url'] . '#main-key',
			'created' => DateTimeFormat::utcNow(DateTimeFormat::ATOM)
		];

		$ohash = self::hash(self::signable_options($options));
		$dhash = self::hash(self::signable_data($data));
		$options['signatureValue'] = base64_encode(Crypto::rsaSign($ohash . $dhash, $owner['uprvkey']));

		return array_merge($data, ['signature' => $options]);
	}

	private static function signable_data($data)
	{
		unset($data['signature']);
		return $data;
	}

	private static function signable_options($options)
	{
		$newopts = ['@context' => 'https://w3id.org/identity/v1'];

		unset($options['type']);
		unset($options['id']);
		unset($options['signatureValue']);

		return array_merge($newopts, $options);
	}

	private static function hash($obj)
	{
		return hash('sha256', JsonLD::normalize($obj));
	}
}
