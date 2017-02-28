<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\HttpKeyStore;

class HttpKeyStoreTest extends TestCase
{
    /**
     * @var HttpKeyStore
     */
    private $store;

    /**
     * @var array
     */
    private static $liveKeys;

    public static function setUpBeforeClass()
    {
        self::$liveKeys = json_decode(file_get_contents(HttpKeyStore::KEYS_URL), true);
    }

    protected function setUp()
    {
        $this->store = new HttpKeyStore();
    }

    public function testGetKey()
    {
        $keyId = array_rand(self::$liveKeys);
        $key = self::$liveKeys[$keyId];

        $this->assertEquals($key, $this->store->get($keyId));
    }

    public function testGetNonExistingKey()
    {
        $this->expectException(\OutOfBoundsException::class);

        $this->store->get('foo');
    }
}
