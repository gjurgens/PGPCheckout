<?php
/*
	Tiny PGP v0.2b
	(c) Brett Trotter 2005-2007 (brett@silcon.com)
	=========================================

	Introduction
	-------------------
	Tiny PGP is actually not PGP compatible at all, but uses some of the fundamentals of PGP
	to create encrypted+signed messages using AES and RSA.
	
	All of the things PGP does to enable multiple message types and encryption methods have been
	stripped out.
	
	Tiny PGP uses mcrypt for Rijndael 256 (AES 256 bit) in order to do the symmetric portion of things
	and Crypt_RSA for the RSA portion. Crypt_RSA is a neat package that can use gmp, big_int, or bcmath
	to perform RSA functions, available from http://pear.php.net/package/Crypt_RSA. Note that I had to
	correct the require_once('') paths, which were wrong, and I went ahead and flattened their multiple
	directory structure- but the code is otherwise unmodified.
	
	How it works
	-------------------
	The basic concept of PGP is you generate a random number to use as a symmetric key to encrypt the body
	You then encrypt the random symmetric key using each desired recipients's public key
	Encode the message using the random symmetric key (binary safe)
	Finally, sign the message using your private key
	
	The receiving code validates the signature of the entire message with the sender's public key
	then tries to fetch the session key back out of the header by attempting to use its private key
	on each of the encrypted session keys (one for each recipient) until the random key is found.
	Once found, the message body is decrypted
	
	Ordinarily, the private keys are encrypted symmetrically for storage and can only be retrieved
	by the person with the password used. I suggest this practice as well, just use aesEncrypt and aesDecrypt.
	The functions expect the unencrypted keys.
	
		
	Bugs
	-------------------
	I'm sure there are many, but the code works fine with a properly formatted message =)
	I'll have to work on 'idiot proofing' later.


	How To
	-------------------
	1) Generate a Crypt_RSA public/private key pair of the desired length. (>=512bytes recommended, 1024 or better is preferred but slow with bcmath)
		    $key_pair = new Crypt_RSA_KeyPair($key_length);
		    $public_key = $key_pair->getPublicKey();
			$private_key = $key_pair->getPrivateKey();

	2) (optionally) Encrypt the private key with aesEncrypt, using compression at your option (make sure to use the same setting for decryption)
			$encryptedKey = aesEncryptV2($private_key, $password, $compress);

			$password should probably be generated with binarySHA256($stringpassword)
			
	2b)	Save your key pair somewhere safe- you'll need it as long as you want to be able to decrypt messages encrypted using this pair
	
	3) When you're ready to send a message:
		a) create an array of the public keys of the people you want to send the message to
			$pubKeys[0] = ...;
			$pubKeys[1] = ...;
		
		b) if you encrypted your private key, decrypt it first
			$private_key = aesDecryptV2($encryptedKey, $password, $compressed);
		
		c) Now create your message. It will automatically be signed with your key.
			$encryptedMessage = create_message($messageBody, $pubKeys, $private_key);

	4) To receive a message:
		a)	If you encrypted your private key, decrypt it first
				$private_key = aesDecryptV2($encryptedKey, $password, $compressed);

		b)	simply decrypt it with:
				$result = decrypt_message($encryptedMessage, $public_key, $private_key);
		
		c)	check for ($result === false) - note the triple equals, otherwise $result contains your message

*/

$SESSION_KEY_LENGTH = 32;
include_once('RSA/RSA.php');

function testKeyPair($pubstring, $privstring) {
  $teststring = "this is a test. this should decrypt correctly.";
  $pubkey = Crypt_RSA_Key::fromString($pubstring);
  if ($pubkey === false) return false;
  $privkey = Crypt_RSA_Key::fromString($privstring);
  if ($privkey === false) return false;

  $rsa_obj = new Crypt_RSA;
  $encrypted = $rsa_obj->encrypt($teststring, $pubkey);
  $decrypted = $rsa_obj->decrypt($encrypted, $privkey);

  return (strcmp($decrypted, $teststring) == 0);
}

// sha256 keymaker
function binarySHA256($string) {
	return hash('sha256', $string, true);
}
function binarySHA256FileStream($inFH) {
	$hashinst = hash_init('sha256');
	fseek($inFH, 0, SEEK_SET);
	hash_update_stream($hashinst, $inFH);
	return hash_final($hashinst, true);
}
function binarySHA256File($filename) {
	return hash_file('sha256', $filename, true);
}
// symmetric encryption using mcrypt

// NOTE: the V1 commands are deprecated and being phased out
function aesEncryptV1($data, $password, $compress) {
	$iv = null;
	if (isset($_SERVER['WINDIR'])) {			// we're in windows, settle for MCRYPT_RAND
		srand((double) microtime() * 1000000);	// seed it
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
	} else {
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_DEV_URANDOM);
	}
	$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $password, $data, MCRYPT_MODE_ECB, $iv);
	$encoded = base64_encode($iv) . "|" . base64_encode($encrypted);
	if ($compress) $encoded = base64_encode(gzcompress($encoded, 9));
	return $encoded;
}
function aesDecryptV1($encrypted, $password, $compressed) {
	if ($compressed) $encrypted = gzuncompress(base64_decode($encrypted));
	if ($encrypted === false) return false;
	$parts = explode("|", $encrypted);
	if (sizeof($parts) != 2) return false;
	$iv = base64_decode($parts[0]);
	if (strlen($iv) != mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB)) return false;
	$encrypted = base64_decode($parts[1]);
	$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $password, $encrypted, MCRYPT_MODE_ECB, $iv);
	return $decrypted;
}
// These are the updated version that are more secure
function aesEncryptV2($data, $password, $compress) {
	//$mcobj = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', 'cbc', '');	
	$mcobj = mcrypt_module_open('rijndael-256', '', 'cbc', '');
	$ivsize = mcrypt_enc_get_iv_size($mcobj);

	$iv = null;
	if (isset($_SERVER['WINDIR'])) {			// we're in windows, settle for MCRYPT_RAND
		srand((double) microtime() * 1000000);	// seed it
		$iv = mcrypt_create_iv($ivsize, MCRYPT_RAND);
	} else {
		$iv = mcrypt_create_iv($ivsize, MCRYPT_DEV_URANDOM);
	}
	mcrypt_generic_init($mcobj, $password, $iv);
	$encrypted = mcrypt_generic($mcobj, $data);
	mcrypt_generic_deinit($mcobj);
	mcrypt_module_close($mcobj);
	
	$encoded = base64_encode($iv) . "|" . base64_encode($encrypted);
	if ($compress) $encoded = base64_encode(gzcompress($encoded, 9));
	return $encoded;
}
function aesDecryptV2($encrypted, $password, $compressed=false) {
	$result = false;

	$mcobj = mcrypt_module_open('rijndael-256', '', 'cbc', '');
	$ivsize = mcrypt_enc_get_iv_size($mcobj);

	if ($compressed) $encrypted = gzuncompress(base64_decode($encrypted));
	if ($encrypted !== false) {
		$parts = explode("|", $encrypted);
		if (sizeof($parts) == 2) {
			$iv = base64_decode($parts[0]);
			if ((strlen($iv) == $ivsize) && ($iv !== false)) {
				mcrypt_generic_init($mcobj, $password, $iv);
				$decoded =  base64_decode($parts[1]);
				if ($decoded !== false) {
					$result = mdecrypt_generic($mcobj, $decoded);
				}
			}
		}
	}
	mcrypt_generic_deinit($mcobj);
	mcrypt_module_close($mcobj);
	return $result;
}
function aesEncryptFile($inFH, $outFH, $IV, $key, $blockspan=10) {
	$readLength = $blockspan*32;					// blockspan tells how many bytes to physically read
									// at a time, experimentally, no difference past ~5 blocks

	// procedure
	// block size of 32 bytes for our setup (256 bits)
	// IV of first block is random, which we save
	// then we read the file in blocks of 32 bytes
	// last block has PKCS#7 padding (fill remaining bytes with number of bytes as the value)
	// if the file length is a multiple of 32, there will be an extra block of padding
	// note that a zero byte file will return a block of encrypted padding

	$mch = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'','cbc','');	// open mcrypt
	mcrypt_generic_init($mch, $key, $IV);				// init IV


	fseek($inFH, 0, SEEK_END);			// go to the end
	$filelength = ftell($inFH);			// how long is the file?
	fseek($inFH, 0, SEEK_SET);			// go to the beginning
	$blocksexpected = ceil($filelength / 32);	// find out how many blocks should be written

	$extrapadding = true;				// default to add a block of padding
	$bytesread = 0;
	$blocksread = 0;
	$blockswritten = 0;
	$bytesthisblock = 0;
	while (!feof($inFH)) {
		$data = fread($inFH, $readLength);
		if ($data === false) break;

		$datastart = 0;
		while ($datastart < strlen($data)) {
			$block = substr($data, $datastart, 32);

			$bytesthisblock = strlen($block);
			$bytesread += $bytesthisblock;
			$blocksread++;

			if ($bytesthisblock < 32) {
				// add padding
				$extrapadding = false;
				$remaining = 32 - $bytesthisblock;
				for ($i=1; $i<=$remaining; $i++) $block .= chr($remaining);		// add # bytes remaining as padding
			}
			$encrypted = mcrypt_generic($mch, $block);
			$result = fwrite($outFH, $encrypted);	// write the block
			if ($result === false) {
				mcrypt_generic_deinit($mch);
				mcrypt_module_close($mch);
				return false;	// error writing
			}
			$blockswritten++;

			$datastart += 32;
		}
	}
	if ($extrapadding) {
		$data = "                                ";	// 32 bytes of ' ' which is chr(32)
		$encrypted = mcrypt_generic($mch, $data);
		$result = fwrite($outFH, $encrypted);	// write the block
		if ($result === false) {
			mcrypt_generic_deinit($mch);
			mcrypt_module_close($mch);
			return false;	// error writing
		}
		$blockswritten++;
	}

	mcrypt_generic_deinit($mch);
	mcrypt_module_close($mch);
	return ($blockswritten == $blocksexpected);
}
function aesPredictEncryptedLength($filesize) {
	$fractionalblocks = ($filesize / 32);
	$fullblocks = ceil($fractionalblocks);

	if ($fractionalblocks == $fullblocks) return (($fullblocks + 1) * 32);	// extra padding for landing on block boundary
	else return ($fullblocks * 32);						// just blocks * 32
}
function aesDecryptFile($inFH, $outFH, $key, $IV, $blockspan=10) {
	$readLength = $blockspan*32;					// blockspan tells how many bytes to physically read
									// at a time, experimentally, no difference past ~5 blocks

	$mch = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'','cbc','');	// open mcrypt
	mcrypt_generic_init($mch, $key, $IV);				// init IV


	fseek($inFH, 0, SEEK_END);			// go to the end
	$filelength = ftell($inFH);			// how long is the file?
	fseek($inFH, 0, SEEK_SET);			// go to the beginning
	$blocksexpected = ceil($filelength / 32);	// find out how many blocks should be written
	if ($blocksexpected != ($filelength / 32)) return false;	// not a valid length

	$blocksread=0;
	$blockswritten=0;
	$bytesread = 0;
	$byteswritten = 0;

	while (!feof($inFH)) {
		$data = fread($inFH, $readLength);
		if ($data === false) break;

		$datastart = 0;
		while ($datastart < strlen($data)) {
			$block = substr($data, $datastart, 32);
			if (strlen($block) != 32) break;

			$bytesread += strlen($block);
			$blocksread++;

			$decrypted = mdecrypt_generic($mch, $block);

			if ($blocksexpected == $blocksread) {
				// last block, check for padding
				$bytes = ord($decrypted[31]);	// how many are we expecting

				// check each one
				for ($i=32-$bytes; $i<32; $i++) {
					if (ord($decrypted[$i]) != $bytes) {
						// $bytes bytes including the last byte should have value $bytes
						mcrypt_generic_deinit($mch);
						mcrypt_module_close($mch);
						return false;
					}
				}
				// padding was ok, now remove it
				$decrypted = substr($decrypted, 0, 32-$bytes);	// chop off the right $bytes bytes
			}
			fwrite($outFH, $decrypted);
			$byteswritten += strlen($decrypted);
			$blockswritten++;

			$datastart += 32;
		}
	}

	mcrypt_generic_deinit($mch);
	mcrypt_module_close($mch);
	return ($blockswritten = $blocksexpected);
}

// message creation
function create_header($pubKeys) {
	$SESSION_KEY_LENGTH = 32;
	// generate a X digit random number to use as the symmetric key
	srand((double) microtime() * 1000000);	//for sake of MCRYPT_RAND
	$tmpKey = "";
	for ($i=0; $i<$SESSION_KEY_LENGTH; $i++) {
		$tmpKey .= rand(0, 9);
	}

	// encrypt the generated session key with each publig key
	$header = "BEGIN HEADER\n";
	$header .= "--------------------------------------------------------------------------------\n";
	for ($i=0; $i<sizeof($pubKeys); $i++) {
	    //$tmppubkey = Crypt_RSA_Key::fromString($pubKeys[$i]);
	    $tmppubkey = Crypt_RSA_Key::fromString($pubKeys[$i]);
	    //check_error($key);
	    $tmprsa = new Crypt_RSA;
		$data = $tmprsa->encrypt("|" . $tmpKey . "|", $tmppubkey);

		$header .= $data . "\n";
	}
	$header .= "--------------------------------------------------------------------------------\n";
	$header .= "END HEADER\n";
	
	$retArray = null;
	$retArray['header'] = $header;
	$retArray['key'] = $tmpKey;
	return $retArray;
}
function create_body($sessionKey, $body) {
	$retval = "\n";
	$retval .= "BEGIN MESSAGE BODY\n";
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= aesEncryptV2($body, $sessionKey, true) . "\n";
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= "END MESSAGE BODY\n";
	
	return $retval;
}
function sign_message($message, $privkey) {
    $tmpkey = Crypt_RSA_Key::fromString($privkey);
    //check_error($key);
	$tmprsa = new Crypt_RSA;
	$data = $tmprsa->createSign($message, $tmpkey);
	$retval = $message . "\n\n";
	$retval .= "BEGIN SIGNATURE\n";
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= $data . "\n";
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= "END SIGNATURE\n";
	return $retval;
}
function create_message($message, $pubKeys, $privateKey) {
	$result = create_header($pubKeys);
	$retval = $result['header'];
	$retval .= create_body($result['key'], $message);
	$retval = sign_message($retval, $privateKey);
	return $retval;
}
function breakup_key($key, $width) {
	$retval = "";
	$startpos = 0;
	while ($startpos < strlen($key)) {
		$retval .= substr($key, $startpos, $width) . "\n";
		$startpos += $width;
	}
	return $retval;
}
function export_private_key($privkey, $username) {
	$retval = "BEGIN PRIVATE KEY (" . $username . ")\n";
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= breakup_key($privkey,80);
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= "END PRIVATE KEY\n";
	return $retval;
}
function export_public_key($pubkey, $username) {
	$retval = "BEGIN PUBLIC KEY (" . $username . ")\n";
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= breakup_key($pubkey,80);
	$retval .= "--------------------------------------------------------------------------------\n";
	$retval .= "END PUBLIC KEY\n";
	return $retval;
}
function extract_private_key($message) {
	$message = str_replace("\n\r", "", $message);	// message was encrypted with \n's only (eg not \r\n)
	$message = str_replace("	", " ", $message);	// convert tabs to spaces

	// now we need to extrac the key from the ASCII armor	
	$start = strpos($message, "BEGIN PRIVATE KEY (");
	if ($start === false) return false;
	$stop = strpos($message, "END PRIVATE KEY");
	if ($stop === false) return false;

	$relevantsection = substr($message, $start, (($stop - $start) + 16));
	$sigParts = explode("\n", $relevantsection);

	for ($i=0; $i<sizeof($sigParts); $i++) {
		$sigParts[$i] = trim($sigParts[$i]);			// get rid of extra whitespace
		if (strlen($sigParts[$i]) == 0) unset($sigParts[$i]);	// get rid of empty elements
	}
	$sigParts = array_merge($sigParts);				// rejoin/renumber parts removed if any
	$tmp = array_slice($sigParts,2,count($sigParts) - 4);
	$key = implode('', $tmp);
	if ((substr($sigParts[0],0,19) != "BEGIN PRIVATE KEY (") || ($sigParts[count($sigParts)-1] != "END PRIVATE KEY")) return false;
	if ((strlen($sigParts[1]) != 80) || (strlen($sigParts[count($sigParts)-2]) != 80)) return false;
	return $key;
}
function extract_public_key($message) {
	$message = str_replace("\r", "", $message);	// message was encrypted with \n's only (eg not \r\n)
	$message = str_replace("	", " ", $message);	// convert tabs to spaces

	// now we need to extrac the key from the ASCII armor	
	$start = strpos($message, "BEGIN PUBLIC KEY (");
	if ($start === false) return false;
	$stop = strpos($message, "END PUBLIC KEY");
	if ($stop === false) return false;

	$relevantsection = substr($message, $start, (($stop - $start) + 16));
	$sigParts = explode("\n", $relevantsection);

	for ($i=0; $i<sizeof($sigParts); $i++) {
		$sigParts[$i] = trim($sigParts[$i]);			// get rid of extra whitespace
		if (strlen($sigParts[$i]) == 0) unset($sigParts[$i]);	// get rid of empty elements
	}
	$sigParts = array_merge($sigParts);				// rejoin/renumber parts removed if any

	$tmp = array_slice($sigParts,2,count($sigParts) - 4);
	$key = implode('', $tmp);

	if ((substr($sigParts[0],0,18) != "BEGIN PUBLIC KEY (") || ($sigParts[count($sigParts)-1] != "END PUBLIC KEY")) return false;
	if ((strlen($sigParts[1]) != 80) || (strlen($sigParts[count($sigParts)-2]) != 80)) return false;
	return $key;
}

// message decryption
function validate_message($message, $pubkey) {
	$message = str_replace("\r", "", $message);	// message was encrypted with \n's only (eg not \r\n)
	// now we need to extrac the signature from the ASCII armor	
	if (strpos($message, "BEGIN SIGNATURE") === false) return false;
	$sigParts = explode("\n", substr($message, strpos($message, "BEGIN SIGNATURE")));
	if (sizeof($sigParts) != 6) return false;
	if (($sigParts[0] != "BEGIN SIGNATURE") || ($sigParts[4] != "END SIGNATURE")) return false;
	if ((strlen($sigParts[1]) != 80) || (strlen($sigParts[3]) != 80)) return false;
	$signature = $sigParts[2];
	
	// save the rest as the message
	$message = substr($message, 0, strpos($message, "BEGIN SIGNATURE") - 1);		// remove signature from the rest for verification
	$lines = explode("\n", $message);												// break into lines
	if (strlen($lines[sizeof($lines) - 1]) == 0) unset($lines[sizeof($lines) - 1]);	// remove empty line at end before signature
	$message = implode("\n", $lines);												// reassemble without extra line

	// validate the signature against the message

	$key = Crypt_RSA_Key::fromString($pubkey);
    //check_error($key);
    $rsa_obj = new Crypt_RSA;
    //check_error($rsa_obj);

    $sigValid = $rsa_obj->validateSign($message, $signature, $key);
    
    // return all parts
    $retArr = null;
    $retArr['message'] = $message;
    $retArr['signature'] = $signature;
    $retArr['valid'] = $sigValid;
	return $retArr;
}
function find_session_key($message, $privateKey) {
	$SESSION_KEY_LENGTH = 32;
	if (strpos($message, "BEGIN HEADER") === false) return false;

	$hdrParts = explode("\n", substr($message, 0, strpos($message, "BEGIN MESSAGE BODY")));
	while (strlen($hdrParts[sizeof($hdrParts) - 1]) == 0) unset($hdrParts[sizeof($hdrParts) - 1]);	// remove empty lines at end before signature

	if (sizeof($hdrParts) < 5) return false;
	if (($hdrParts[0] != "BEGIN HEADER") || ($hdrParts[sizeof($hdrParts) - 1] != "END HEADER")) return false;
	if ((strlen($hdrParts[1]) != 80) || (strlen($hdrParts[sizeof($hdrParts) - 2]) != 80)) return false;
	
	// remove armor
	$oldMax = sizeof($hdrParts) - 1;
	unset($hdrParts[0]);
	unset($hdrParts[1]);
	unset($hdrParts[$oldMax - 1]);
	unset($hdrParts[$oldMax]);
	$keys = array_merge($hdrParts);
	
	$message = substr($message, strpos($message, "BEGIN MESSAGE BODY"));		// remove header from the rest for verification
	$lines = explode("\n", $message);												// break into lines
	while (strlen($lines[sizeof($lines) - 1]) == 0) unset($lines[sizeof($lines) - 1]);	// remove empty lines at end before signature
	
	// remove armor
	$oldMax = sizeof($lines) - 1;
	unset($lines[0]);
	unset($lines[1]);
	unset($lines[$oldMax - 1]);
	unset($lines[$oldMax]);
	$message = implode("\n", $lines);												// reassemble without extra line

	$session = null;
	// attempt decoding of each
	for ($i=0; $i<sizeof($keys); $i++) {
	    $key = Crypt_RSA_Key::fromString($privateKey);
	    //check_error($key);
	    $rsa_obj = new Crypt_RSA;
		//check_error($rsa_obj);
	    $data = $rsa_obj->decrypt($keys[$i], $key);
	    if ($data !== false) {
			$parts = explode("|", $data);
			if ((sizeof($parts) == 3) && (strlen($parts[1]) == $SESSION_KEY_LENGTH)) {
				// found our session key
				$session = $parts[1];
				break;
			}
		}
	}
	
	if ($session == null) return false;
	
	$retArr = null;
	$retArr['keys'] = $keys;
	$retArr['session'] = $session;
	$retArr['message'] = $message;
	return $retArr;
}
function decrypt_message($message, $publicKey, $privateKey) {
	$result = validate_message($message, $publicKey);
	if ($result === false) return false;
	if ($result['valid'] == false) return false;
	$result2 = find_session_key($result['message'], $privateKey);
	if ($result2 === false) return false;
	
	$result3 = aesDecryptV2($result2['message'], $result2['session'], true);
	return $result3;
}

// base64 validator - just checks to make sure all characters are in range
function base64_validate($data) {
	if (strlen($data) == 0) return false;
	/*
	$fh = fopen("/tmp/data.txt", "w");
	fwrite($fh, $data);
	fclose($fh);
	*/
	for ($i=0;$i<strlen($data); $i++) {
		$char = ord(substr($data, $i, 1));	// get value
		$charValid = false;
		if ((($char > 64) && ($char < 91)) || (($char > 96) && ($char < 123)) || (($char > 46) && ($char < 58))  || (($char == 43) || ($char == 61)) || (($char == 10) || ($char == 13)) || (($char == 0) || ($char == 32))) $charValid = true;
		
		if (!$charValid) {
			return false; 
		}
	}
	return true;
}
?>
