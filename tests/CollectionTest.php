<?php

use IrfanTOOR\Collection;
use IrfanTOOR\Test;

class CollectionTest extends Test
{
    function getInit()
    {
        return [
            'null'  => null,
            'hello' => 'world!',
            'app'   => [
                'name'    => 'My App',
                'version' => '1.1',
            ]
        ];
    }

    function getCollection($init = null)
    {
        if (!$init) {
            $init = $this->getInit();
        }

        return new Collection($init);
    }

    function testCollectionInstance()
    {
        $c = $this->getCollection();

        $this->assertInstanceOf(Collection::class, $c);
    }

    function testInit()
    {
        $c = new Collection();
        $this->assertArray($c->toArray());
        $this->assertEquals([], $c->toArray());

        $c = $this->getCollection();
        $this->assertEquals('world!', $c->get('hello'));
        $this->assertEquals('My App', $c->get('app.name'));
        $this->assertEquals('1.1', $c->get('app.version'));

        $init = [
            'hello' => 'World!',
        ];

        $c = $this->getCollection($init);
        $this->assertEquals(1, $c->count());
        $this->assertEquals('World!', $c->get('hello'));
    }

    function testVersion()
    {
        $c = $this->getCollection();
        $version = Collection::VERSION;
        $this->assertNotEmpty($c::VERSION);
        $this->assertString($c::VERSION);
        $this->assertEquals($version, $c::VERSION);
    }

    function testLocked()
    {
        $c = $this->getCollection();
        $c->lock();

        # set

        $result = $c->set('hello', 'someone');
        $this->assertFalse($result);
        $this->assertEquals('world!', $c->get('hello'));

        $result = $c->set('undefined', 'something');
        $this->assertFalse($result);
        $this->assertNull($c->get('undefined'));

        $this->assertNull($c->get('something'));
        $this->assertEquals('default', $c->get('something', 'default'));

        # set for the first time
        $c->set('something', 'defined');

        $this->assertNull($c->get('something'));
        $this->assertEquals('default', $c->get('something', 'default'));

        # assign a new value
        $c->set('something', 'somethingelse');
        $this->assertEquals('default', $c->get('something', 'default'));

        # predfined
        $this->assertEquals('1.1', $c->get('app.version'));
        $c->set('app.version', '1.2');
        $this->assertEquals('1.1', $c->get('app.version'));
        $this->assertEquals('1.1', $c->get('app')['version']);

        # remove
        $this->assertTrue($c->has('null'));
        $this->assertTrue($c->has('hello'));

        # remove an element using method 'remove'
        $c->remove('null');
        $c->remove('hello');
        $this->assertTrue($c->has('null'));
        $this->assertTrue($c->has('hello'));

        # remove another element using unset and array access
        $this->assertTrue($c->has('app.version'));
        $c->remove('app.version');
        $this->assertTrue($c->has('app.name'));
        $this->assertTrue($c->has('app.version'));
    }

    function testHas()
    {
        $c = $this->getCollection();

        # defined elements
        $this->assertTrue($c->has('null'));
        $this->assertTrue($c->has('hello'));
        $this->assertTrue($c->has('app.name'));
        $this->assertTrue($c->has('app.version'));

        # array access
        $this->assertTrue(isset($c['null']));
        $this->assertTrue(isset($c['hello']));
        $this->assertTrue(isset($c['app.name']));
        $this->assertTrue(isset($c['app.version']));


        # undefined elements
        $this->assertFalse($c->has('nothing'));
        $this->assertFalse($c->has('app.author'));
        $this->assertFalse($c->has('app.version.test'));

        # array access
        $this->assertFalse(isset($c['nothing']));
        $this->assertFalse(isset($c['app.author']));
        $this->assertFalse(isset($c['app.version.test']));
    }

    function testGet()
    {
        $c = $this->getCollection();

        # defined elements
        $this->assertEquals(null,     $c->get('null'));
        $this->assertEquals('world!', $c->get('hello'));
        $this->assertEquals('My App', $c->get('app.name'));
        $this->assertEquals('1.1',    $c->get('app.version'));

        # array access
        $this->assertEquals('world!', $c['hello']);
        $this->assertEquals('My App', $c['app.name']);
        $this->assertEquals('1.1', $c['app.version']);
        $this->assertEquals('1.1', $c['app']['version']);

        # undefined elements
        $this->assertNull($c->get('something'));
        $this->assertNull($c->get('undefined'));
        $this->assertNull($c->get('app.author'));

        # array access
        $this->assertNull($c['something']);
        $this->assertNull($c['undefined']);
        $this->assertNull($c['app.author']);

        # default behaviour
        $this->assertNull($c->get('null', 'default'));
        $this->assertEquals('world!',      $c->get('hello',     'now-default'));
        $this->assertEquals('default',     $c->get('something', 'default'));
        $this->assertEquals('now-default', $c->get('undefined', 'now-default'));

        # array access can be used by return isset($c[$key]) ? $c[$key] : $default
        $this->assertEquals('default', isset($c['something']) ? $c['something'] : 'default');

        $this->assertEquals('it', $c->get('app.author', 'it'));
    }

    function testSet()
    {
        $c = $this->getCollection();

        # make sure that something is not defined
        $this->assertFalse($c->has('something'));
        $this->assertFalse($c->has('something_a'));

        # set for the first time
        $c->set('something', 'defined');
        $c['something_a'] = 'defined';

        $this->assertEquals('defined', $c->get('something'));
        $this->assertEquals('defined', $c->get('something', 'default'));

        $this->assertEquals('defined', $c->get('something_a'));
        $this->assertEquals('defined', $c->get('something_a', 'default'));

        # assign a new value
        $c->set('something', 'somethingelse');
        $this->assertEquals('somethingelse', $c->get('something', 'default'));

        $c['something_a'] = 'somethingelse';
        $this->assertEquals('somethingelse', $c->get('something', 'default'));

        # array can not be extended inside a string
        $this->assertFalse($c->set('something.deep', 'abyss'));
        $c['something.deep'] = 'abyss';
        $this->assertNotEquals('abyss', 'something.deep');

        # predfined
        $this->assertEquals('1.1', $c->get('app.version'));
        $c->set('app.version', '1.2');
        $this->assertEquals('1.2', $c->get('app.version'));
        $this->assertEquals('1.2', $c->get('app')['version']);

        # setting using array access
        $c['app.version'] = '1.1';
        $this->assertEquals('1.1', $c['app.version']);
        $this->assertEquals('1.1', $c['app']['version']);

        # set for the first time
        $this->assertNull($c->get('certification'));
        $this->assertNull($c['certification']);
        $c->set('certification.authority', ['name' => 'CA', 'address' => 'somewhere' ]);
        $this->assertEquals('CA', $c->get('certification.authority.name'));
        $this->assertEquals('somewhere', $c->get('certification.authority.address'));

        $this->assertEquals('CA', $c['certification.authority.name']);
        $this->assertEquals('somewhere', $c['certification.authority.address']);
    }

    function testSetMultipleUsingAnArray()
    {
        $c = $this->getCollection();

        $this->assertNull($c->get('something'));
        $this->assertNull($c->get('undefined'));

        $c->setMultiple(
            [
                'something' => 'defined',
                'undefined' => 'now-defined'
            ]
        );

        $this->assertEquals('defined', $c['something']);
        $this->assertEquals('now-defined', $c->get('undefined', 'default'));
    }

    function testRemove()
    {
        $c = $this->getCollection();

        $this->assertTrue($c->has('null'));
        $this->assertTrue($c->has('hello'));

        # remove an element using method 'remove'
        $c->remove('null');
        $this->assertFalse($c->has('null'));
        $this->assertTrue($c->has('hello'));

        # remove another element using unset and array access
        $c->remove('hello');
        $this->assertFalse($c->has('null'));
        $this->assertFalse($c->has('hello'));

        # remove another element using unset and array access
        $this->assertTrue($c->has('app.version'));

        $c->remove('app.version');
        $this->assertTrue($c->has('app.name'));
        $this->assertFalse($c->has('app.version'));

        # using array access
        $c = $this->getCollection();

        $this->assertTrue(isset($c['null']));
        $this->assertTrue(isset($c['hello']));

        # remove an element using unset
        unset($c['null']);
        $this->assertFalse(isset($c['null']));
        $this->assertTrue(isset($c['hello']));

        # remove another element using unset and array access
        $c->remove('hello');
        $this->assertFalse(isset($c['null']));
        $this->assertFalse(isset($c['hello']));

        # remove another element using unset and array access
        $this->assertTrue(isset($c['app.version']));

        unset($c['app.version']);
        $this->assertTrue($c->has('app.name'));
        $this->assertFalse($c->has('app.version'));
    }

    function testToArray()
    {
        $init = [
            'null'  => null,
            'hello' => 'world!',
            'array' => [
                'a' => 'A',
                'b' => 'B'
            ]
        ];

        $c = $this->getCollection($init);
        $a = $c->toArray();

        $this->assertEquals($init, $a);
    }

    function testKeys()
    {
        $init = [
            'null'  => null,
            'hello' => 'world!',
            'array' => [
                'a' => 'A',
                'b' => 'B'
            ]
        ];

        $c = $this->getCollection($init);
        $keys = $c->keys();

        $this->assertEquals(array_keys($init), $keys);
    }

    function testCount()
    {
        $init = [
            'null'  => null,
            'hello' => 'world!',
            'array' => [
                'a' => 'A',
                'b' => 'B'
            ]
        ];

        $c = $this->getCollection($init);

        $this->assertEquals(3, $c->count());

        $c->remove('array.a');
        $this->assertEquals(3, $c->count());

        $c->remove('array');
        $this->assertEquals(2, $c->count());

        $c->remove('null');
        $this->assertEquals(1, $c->count());
    }

    function testFilter()
    {
        $c = $this->getCollection();
        $some_more = [
            1 => 1,
            2 => 'two',
            3 => 3,
        ];

        $c->setMultiple($some_more);

        $d = $c->filter(function ($key, $value) {
            return false;
        });

        $d = $c->filter(function () {
            return true;
        });

        $this->assertInstanceOf(Collection::class, $d);
        $this->assertEquals($c, $d);

        $d = $c->filter(function ($key, $value) {
            return is_int($key);
        });

        $this->assertEquals($some_more, $d->toArray());

        $d = $c->filter(function ($key, $value) {
            return is_array($value);
        });

        $this->assertEquals(1, $d->count());
        $this->assertFalse($d->has('null'));
        $this->assertFalse($d->has('hello'));
        $this->assertTrue($d->has('app'));
    }

    function testMap()
    {
        $c = $this->getCollection();
        $some_more = [
            1 => 1,
            2 => 'two',
            3 => 3,
        ];

        $c->setMultiple($some_more);

        $d = $c->map(function ($key, $value) {
            return $value;
        });

        $this->assertInstanceOf(Collection::class, $d);
        $this->assertEquals($c, $d);

        $d = $c->filter(
            function ($key, $value) {
                return is_int($value);
            }
        )->map(
            function ($key, $value) {
                return $value * $value;
            }
        );

        $this->assertEquals([1 => 1, 3 => 9], $d->toArray());
    }

    function testReduce()
    {
        $c = $this->getCollection();
        $some_more = [
            1 => 1,
            2 => 'two',
            3 => 3,
            4 => 16,
        ];

        $c->setMultiple($some_more);

        $d = $c->reduce(
            function ($key, $value, $carry) {
                return $carry;
            }
        );

        $this->assertNull($d);

        $c->setMultiple($some_more);

        $d = $c->reduce(
            function ($key, $value, $carry) {
                return 0;
            }
        );

        $this->assertZero($d);

        $c->setMultiple($some_more);

        $d = $c->reduce(
            function ($key, $value, $carry) {
                return is_int($value) ? $carry + (int) $value : $carry;
            }
        );

        $this->assertEquals(20, $d);

        $d = $c->reduce(function ($key, $value, $carry) {
            $total =
                $carry +
                (is_int($value) ? $value : 0) -
                (is_int($key) ? $key : 0);

            return $total;
        });

        $this->assertEquals(10, $d);


        $d = $c->filter(
            function ($key, $value) {
                return is_int($value);
            }
        )->reduce(
            function ($key, $value, $carry) {
                return $carry + (int) $value - (int) $key;
            }
        );

        $this->assertInt($d);
        $this->assertEquals(12, $d);
    }

    function testJsonEncodable()
    {
        $c = $this->getCollection();
        $array2json = json_encode($c->toArray());
        $class2json = json_encode($c);

        $this->assertSame($array2json, $class2json);
    }
}
