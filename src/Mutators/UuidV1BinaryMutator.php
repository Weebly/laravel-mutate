<?php

namespace Weebly\Mutate\Mutators;

use Exception;
use Ramsey\Uuid\Uuid;
use Weebly\Mutate\Exceptions\MutateException;

class UuidV1BinaryMutator implements MutatorContract
{
    /**
     * {@inheritdoc}
     */
    public function serializeAttribute($value)
    {
        try {
            /** @var \Ramsey\Uuid\Uuid $uuid */
            $uuid = (ctype_print($value)) ? Uuid::getFactory()->fromString($value) : Uuid::getFactory()->fromBytes($value);
        } catch (Exception $e) {
            throw new MutateException("Cannot serialize invalid UUID: {$value}");
        }

        // Breakout if the UUID is not the correct version
        if ($uuid->getVersion() !== 1) {
            throw new MutateException('Incorrect UUID version');
        }

        $pieces = $uuid->getFieldsHex();
        $ordered = sprintf(
            '%s%s%s%s%s%s',
            $pieces['time_hi_and_version'],
            $pieces['time_mid'],
            $pieces['time_low'],
            $pieces['clock_seq_hi_and_reserved'],
            $pieces['clock_seq_low'],
            $pieces['node']
        );

        return hex2bin($ordered);
    }

    /**
     * {@inheritdoc}
     */
    public function unserializeAttribute($value)
    {
        if (! $value) {
            return;
        }

        // Convert to hex
        $hex = bin2hex($value);

        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 8, 8),
            substr($hex, 4, 4),
            substr($hex, 0, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );

        try {
            /** @var \Ramsey\Uuid\Uuid $uuid */
            $uuid = Uuid::getFactory()->fromString($uuid);
        } catch (Exception $e) {
            return;
        }

        // Validate the UUID version
        if ($uuid->getVersion() !== 1) {
            return;
        }

        return $uuid->toString();
    }
}
