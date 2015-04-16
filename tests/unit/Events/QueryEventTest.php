<?php

namespace Behance\NBD\Cache\Events;

use Behance\NBD\Cache\Test\BaseTest;

class QueryEventTest extends BaseTest {

  /**
   * @test
   * @dataProvider constructProvider
   */
  public function construct( $operation, $key_or_keys, $mutable ) {

    $event = new QueryEvent( $operation, $key_or_keys, $mutable );

    $this->assertEquals( $operation, $event->getOperation() );
    $this->assertEquals( $key_or_keys, $event->getKey() );
    $this->assertEquals( is_array( $key_or_keys ), $event->hasMultipleKeys() );
    $this->assertEquals( $mutable, $event->isMutable() );

  } // construct

  /**
   * @return array
   */
  public function constructProvider() {

    return [
        [ 'get', 'abcefg', false ],
        [ 'add', 'abcefg', true ],
        [ 'getMulti',    [ 'abc', 'efg' ], false ],
        [ 'deleteMulti', [ 'abc', 'efg' ], true ],
    ];

  } // constructProvider

} // QueryEventTest
