<?php

return [
    'enabled' => [
        /*
         * Eloquent mutator providers...
         */
        'uuid_v1_binary' => \Weebly\Mutate\Mutators\UuidV1BinaryMutator::class,
        'ip_binary'      => \Weebly\Mutate\Mutators\IpBinaryMutator::class,
        'encrypt_string' => \Weebly\Mutate\Mutators\EncryptStringMutator::class,
    ],
];
