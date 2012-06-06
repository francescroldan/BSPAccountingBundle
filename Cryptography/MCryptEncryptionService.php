<?php

namespace BSP\AccountingBundle\Cryptography;

/**
 * This implementation transmits the initialization vector (IV) alongside
 * the encrypted data. Choose your cipher/mode combination with care as
 * this might severely compromise the strength of the applied algorithm.
 *
 * Defaults should be fine for almost all cases.
 *
 */
class MCryptEncryptionService implements EncryptionServiceInterface
{
    protected $cipher;
    protected $key;
    protected $mode;

    /**
     * Constructor
     *
     * @param string $secret
     * @param string $cipher
     * @param string $mode
     */
    public function __construct($secret, $cipher = 'rijndael-256', $mode = 'ctr')
    {
        if (!extension_loaded('mcrypt')) {
            throw new \RuntimeException('The mcrypt extension must be loaded.');
        }

        if (!in_array($cipher, mcrypt_list_algorithms(), true)) {
            throw new \InvalidArgumentException(sprintf('The cipher "%s" is not supported.', $cipher));
        }

        if (!in_array($mode, mcrypt_list_modes(), true)) {
            throw new \InvalidArgumentException(sprintf('The mode "%s" is not supported.', $mode));
        }

        $this->cipher = $cipher;
        $this->mode = $mode;

        if (0 === strlen($secret)) {
            throw new \InvalidArgumentException('$secret must not be empty.');
        }

        $key = hash('sha256', $secret, true);
        if (strlen($key) > $size = mcrypt_get_key_size($this->cipher, $this->mode)) {
            $key = substr($key, 0, $size);
        }
        $this->key = $key;
    }

    /**
     * {@inheritDoc}
     */
    public function decrypt($encryptedValue)
    {
        $size = mcrypt_get_iv_size($this->cipher, $this->mode);
        $encryptedValue = base64_decode($encryptedValue);
        $iv = substr($encryptedValue, 0, $size);

        return rtrim(mcrypt_decrypt($this->cipher, $this->key, substr($encryptedValue, $size), $this->mode, $iv));
    }

    /**
     * {@inheritDoc}
     */
    public function encrypt($rawValue)
    {
        $size = mcrypt_get_iv_size($this->cipher, $this->mode);
        $iv = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

        return base64_encode($iv.mcrypt_encrypt($this->cipher, $this->key, $rawValue, $this->mode, $iv));
    }

    public function getCipher()
    {
        return $this->cipher;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getMode()
    {
        return $this->mode;
    }
}