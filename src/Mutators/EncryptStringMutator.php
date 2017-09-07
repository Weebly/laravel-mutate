<?php

namespace Weebly\Mutate\Mutators;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

class EncryptStringMutator implements MutatorContract
{
    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypt;

    /**
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypt
     */
    public function __construct(Encrypter $encrypt)
    {
        $this->encrypt = $encrypt;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeAttribute($value)
    {
        try {
            return $this->encrypt->encrypt($value, false);
        } catch (DecryptException $e) {}

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeAttribute($value)
    {
        try {
            return $this->encrypt->decrypt($value, false);
        } catch (DecryptException $e) {}

        return null;
    }
}
