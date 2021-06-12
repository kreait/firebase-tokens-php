<?php

declare(strict_types=1);

function generate(string $name): void
{
    $key = \openssl_pkey_new([
        'digest_alg' => 'sha512',
        'private_key_bits' => 4096,
        'private_key_type' => \OPENSSL_KEYTYPE_RSA,
    ]);

    \openssl_pkey_export($key, $private);
    \file_put_contents(__DIR__."/{$name}.key", $private);
    \file_put_contents(__DIR__."/{$name}.pub", \openssl_pkey_get_details($key)['key']);
}

\generate('one');
\generate('other');
