<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Util;

use ASN_BASE;
use ASNValue;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;

/**
 * Crypto class
 */
class Crypto
{
	// supported algorithms are 'sha256', 'sha1'
	/**
	 * @param string $data data
	 * @param string $key  key
	 * @param string $alg  algorithm
	 * @return string
	 */
	public static function rsaSign($data, $key, $alg = 'sha256')
	{
		if (empty($key)) {
			Logger::warning('Empty key parameter', ['callstack' => System::callstack()]);
		}
		openssl_sign($data, $sig, $key, (($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
		return $sig;
	}

	/**
	 * @param string $data data
	 * @param string $sig  signature
	 * @param string $key  key
	 * @param string $alg  algorithm
	 * @return boolean
	 */
	public static function rsaVerify($data, $sig, $key, $alg = 'sha256')
	{
		if (empty($key)) {
			Logger::warning('Empty key parameter', ['callstack' => System::callstack()]);
		}
		return openssl_verify($data, $sig, $key, (($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
	}

	/**
	 * @param string $Der     der formatted string
	 * @param bool   $Private key type optional, default false
	 * @return string
	 */
	private static function DerToPem($Der, $Private = false)
	{
		//Encode:
		$Der = base64_encode($Der);
		//Split lines:
		$lines = str_split($Der, 65);
		$body = implode("\n", $lines);
		//Get title:
		$title = $Private ? 'RSA PRIVATE KEY' : 'PUBLIC KEY';
		//Add wrapping:
		$result = "-----BEGIN {$title}-----\n";
		$result .= $body . "\n";
		$result .= "-----END {$title}-----\n";

		return $result;
	}

	/**
	 * @param string $Der der formatted string
	 * @return string
	 */
	private static function DerToRsa($Der)
	{
		//Encode:
		$Der = base64_encode($Der);
		//Split lines:
		$lines = str_split($Der, 64);
		$body = implode("\n", $lines);
		//Get title:
		$title = 'RSA PUBLIC KEY';
		//Add wrapping:
		$result = "-----BEGIN {$title}-----\n";
		$result .= $body . "\n";
		$result .= "-----END {$title}-----\n";

		return $result;
	}

	/**
	 * @param string $Modulus        modulo
	 * @param string $PublicExponent exponent
	 * @return string
	 */
	private static function pkcs8Encode($Modulus, $PublicExponent)
	{
		//Encode key sequence
		$modulus = new ASNValue(ASNValue::TAG_INTEGER);
		$modulus->SetIntBuffer($Modulus);
		$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
		$publicExponent->SetIntBuffer($PublicExponent);
		$keySequenceItems = [$modulus, $publicExponent];
		$keySequence = new ASNValue(ASNValue::TAG_SEQUENCE);
		$keySequence->SetSequence($keySequenceItems);
		//Encode bit string
		$bitStringValue = $keySequence->Encode();
		$bitStringValue = chr(0x00) . $bitStringValue; //Add unused bits byte
		$bitString = new ASNValue(ASNValue::TAG_BITSTRING);
		$bitString->Value = $bitStringValue;
		//Encode body
		$bodyValue = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00" . $bitString->Encode();
		$body = new ASNValue(ASNValue::TAG_SEQUENCE);
		$body->Value = $bodyValue;
		//Get DER encoded public key:
		$PublicDER = $body->Encode();
		return $PublicDER;
	}

	/**
	 * @param string $Modulus        modulo
	 * @param string $PublicExponent exponent
	 * @return string
	 */
	private static function pkcs1Encode($Modulus, $PublicExponent)
	{
		//Encode key sequence
		$modulus = new ASNValue(ASNValue::TAG_INTEGER);
		$modulus->SetIntBuffer($Modulus);
		$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
		$publicExponent->SetIntBuffer($PublicExponent);
		$keySequenceItems = [$modulus, $publicExponent];
		$keySequence = new ASNValue(ASNValue::TAG_SEQUENCE);
		$keySequence->SetSequence($keySequenceItems);
		//Encode bit string
		$bitStringValue = $keySequence->Encode();
		return $bitStringValue;
	}

	/**
	 * @param string $m modulo
	 * @param string $e exponent
	 * @return string
	 */
	public static function meToPem($m, $e)
	{
		$der = self::pkcs8Encode($m, $e);
		$key = self::DerToPem($der, false);
		return $key;
	}

	/**
	 * @param string $key key
	 * @param string $m   modulo reference
	 * @param object $e   exponent reference
	 * @return void
	 * @throws \Exception
	 */
	private static function pubRsaToMe($key, &$m, &$e)
	{
		$lines = explode("\n", $key);
		unset($lines[0]);
		unset($lines[count($lines)]);
		$x = base64_decode(implode('', $lines));

		$r = ASN_BASE::parseASNString($x);

		$m = Strings::base64UrlDecode($r[0]->asnData[0]->asnData);
		$e = Strings::base64UrlDecode($r[0]->asnData[1]->asnData);
	}

	/**
	 * @param string $key key
	 * @return string
	 * @throws \Exception
	 */
	public static function rsaToPem($key)
	{
		self::pubRsaToMe($key, $m, $e);
		return self::meToPem($m, $e);
	}

	/**
	 * @param string $key key
	 * @return string
	 * @throws \Exception
	 */
	private static function pemToRsa($key)
	{
		self::pemToMe($key, $m, $e);
		return self::meToRsa($m, $e);
	}

	/**
	 * @param string $key key
	 * @param string $m   modulo reference
	 * @param string $e   exponent reference
	 * @return void
	 * @throws \Exception
	 */
	public static function pemToMe($key, &$m, &$e)
	{
		$lines = explode("\n", $key);
		unset($lines[0]);
		unset($lines[count($lines)]);
		$x = base64_decode(implode('', $lines));

		$r = ASN_BASE::parseASNString($x);

		if (isset($r[0])) {
			$m = Strings::base64UrlDecode($r[0]->asnData[1]->asnData[0]->asnData[0]->asnData);
			$e = Strings::base64UrlDecode($r[0]->asnData[1]->asnData[0]->asnData[1]->asnData);
		}
	}

	/**
	 * @param string $m modulo
	 * @param string $e exponent
	 * @return string
	 */
	private static function meToRsa($m, $e)
	{
		$der = self::pkcs1Encode($m, $e);
		$key = self::DerToRsa($der);
		return $key;
	}

	/**
	 * @param integer $bits number of bits
	 * @return mixed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function newKeypair($bits)
	{
		$openssl_options = [
			'digest_alg'       => 'sha1',
			'private_key_bits' => $bits,
			'encrypt_key'      => false
		];

		$conf = DI::config()->get('system', 'openssl_conf_file');
		if ($conf) {
			$openssl_options['config'] = $conf;
		}
		$result = openssl_pkey_new($openssl_options);

		if (empty($result)) {
			Logger::log('new_keypair: failed');
			return false;
		}

		// Get private key
		$response = ['prvkey' => '', 'pubkey' => ''];

		openssl_pkey_export($result, $response['prvkey']);

		// Get public key
		$pkey = openssl_pkey_get_details($result);
		$response['pubkey'] = $pkey["key"];

		return $response;
	}

	/**
	 * Encrypt a string with 'aes-256-cbc' cipher method.
	 * 
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 * 
	 * @param string $data
	 * @param string $key   The key used for encryption.
	 * @param string $iv    A non-NULL Initialization Vector.
	 * 
	 * @return string|boolean Encrypted string or false on failure.
	 */
	private static function encryptAES256CBC($data, $key, $iv)
	{
		return openssl_encrypt($data, 'aes-256-cbc', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 * Decrypt a string with 'aes-256-cbc' cipher method.
	 * 
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 * 
	 * @param string $data
	 * @param string $key   The key used for decryption.
	 * @param string $iv    A non-NULL Initialization Vector.
	 * 
	 * @return string|boolean Decrypted string or false on failure.
	 */
	private static function decryptAES256CBC($data, $key, $iv)
	{
		return openssl_decrypt($data, 'aes-256-cbc', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 * Encrypt a string with 'aes-256-ctr' cipher method.
	 * 
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 * 
	 * @param string $data
	 * @param string $key   The key used for encryption.
	 * @param string $iv    A non-NULL Initialization Vector.
	 * 
	 * @return string|boolean Encrypted string or false on failure.
	 */
	private static function encryptAES256CTR($data, $key, $iv)
	{
		$key = substr($key, 0, 32);
		$iv = substr($iv, 0, 16);
		return openssl_encrypt($data, 'aes-256-ctr', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 * Decrypt a string with 'aes-256-ctr' cipher method.
	 * 
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 * 
	 * @param string $data
	 * @param string $key   The key used for decryption.
	 * @param string $iv    A non-NULL Initialization Vector.
	 * 
	 * @return string|boolean Decrypted string or false on failure.
	 */
	private static function decryptAES256CTR($data, $key, $iv)
	{
		$key = substr($key, 0, 32);
		$iv = substr($iv, 0, 16);
		return openssl_decrypt($data, 'aes-256-ctr', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $pubkey The public key.
	 * @param string $alg    The algorithm used for encryption.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function encapsulate($data, $pubkey, $alg = 'aes256cbc')
	{
		if ($alg === 'aes256cbc') {
			return self::encapsulateAes($data, $pubkey);
		}
		return self::encapsulateOther($data, $pubkey, $alg);
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $pubkey The public key.
	 * @param string $alg    The algorithm used for encryption.
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function encapsulateOther($data, $pubkey, $alg)
	{
		if (!$pubkey) {
			Logger::log('no key. data: '.$data);
		}
		$fn = 'encrypt' . strtoupper($alg);
		if (method_exists(__CLASS__, $fn)) {
			$result = ['encrypted' => true];
			$key = random_bytes(256);
			$iv  = random_bytes(256);
			$result['data'] = Strings::base64UrlEncode(self::$fn($data, $key, $iv), true);

			// log the offending call so we can track it down
			if (!openssl_public_encrypt($key, $k, $pubkey)) {
				$x = debug_backtrace();
				Logger::notice('RSA failed', ['trace' => $x[0]]);
			}

			$result['alg'] = $alg;
			$result['key'] = Strings::base64UrlEncode($k, true);
			openssl_public_encrypt($iv, $i, $pubkey);
			$result['iv'] = Strings::base64UrlEncode($i, true);

			return $result;
		} else {
			$x = ['data' => $data, 'pubkey' => $pubkey, 'alg' => $alg, 'result' => $data];
			Hook::callAll('other_encapsulate', $x);

			return $x['result'];
		}
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $pubkey
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function encapsulateAes($data, $pubkey)
	{
		if (!$pubkey) {
			Logger::log('aes_encapsulate: no key. data: ' . $data);
		}

		$key = random_bytes(32);
		$iv  = random_bytes(16);
		$result = ['encrypted' => true];
		$result['data'] = Strings::base64UrlEncode(self::encryptAES256CBC($data, $key, $iv), true);

		// log the offending call so we can track it down
		if (!openssl_public_encrypt($key, $k, $pubkey)) {
			$x = debug_backtrace();
			Logger::log('aes_encapsulate: RSA failed. ' . print_r($x[0], true));
		}

		$result['alg'] = 'aes256cbc';
		$result['key'] = Strings::base64UrlEncode($k, true);
		openssl_public_encrypt($iv, $i, $pubkey);
		$result['iv'] = Strings::base64UrlEncode($i, true);

		return $result;
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param array $data ['iv' => $iv, 'key' => $key, 'alg' => $alg, 'data' => $data]
	 * @param string $prvkey The private key used for decryption.
	 *
	 * @return string|boolean The decrypted string or false on failure.
	 * @throws \Exception
	 */
	public static function unencapsulate(array $data, $prvkey)
	{
		if (!$data) {
			return;
		}

		$alg = ((array_key_exists('alg', $data)) ? $data['alg'] : 'aes256cbc');
		if ($alg === 'aes256cbc') {
			return self::encapsulateAes($data['data'], $prvkey);
		}
		return self::encapsulateOther($data['data'], $prvkey, $alg);
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param array $data
	 * @param string $prvkey The private key used for decryption.
	 * @param string $alg
	 *
	 * @return string|boolean The decrypted string or false on failure.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function unencapsulateOther(array $data, $prvkey, $alg)
	{
		$fn = 'decrypt' . strtoupper($alg);

		if (method_exists(__CLASS__, $fn)) {
			openssl_private_decrypt(Strings::base64UrlDecode($data['key']), $k, $prvkey);
			openssl_private_decrypt(Strings::base64UrlDecode($data['iv']), $i, $prvkey);

			return self::$fn(Strings::base64UrlDecode($data['data']), $k, $i);
		} else {
			$x = ['data' => $data, 'prvkey' => $prvkey, 'alg' => $alg, 'result' => $data];
			Hook::callAll('other_unencapsulate', $x);

			return $x['result'];
		}
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param array  $data
	 * @param string $prvkey The private key used for decryption.
	 *
	 * @return string|boolean The decrypted string or false on failure.
	 * @throws \Exception
	 */
	private static function unencapsulateAes($data, $prvkey)
	{
		openssl_private_decrypt(Strings::base64UrlDecode($data['key']), $k, $prvkey);
		openssl_private_decrypt(Strings::base64UrlDecode($data['iv']), $i, $prvkey);

		return self::decryptAES256CBC(Strings::base64UrlDecode($data['data']), $k, $i);
	}


	/**
	 * Creates cryptographic secure random digits
	 *
	 * @param string $digits The count of digits
	 * @return int The random Digits
	 *
	 * @throws \Exception In case 'random_int' isn't usable
	 */
	public static function randomDigits($digits)
	{
		$rn = '';

		// generating cryptographically secure pseudo-random integers
		for ($i = 0; $i < $digits; $i++) {
			$rn .= random_int(0, 9);
		}

		return $rn;
	}
}
