<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Logger;

use CommerceFlow\Logger\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Logger unit tests.
 *
 * These verify the logger auto-creates and handles calls gracefully
 * when WooCommerce's wc_get_logger() is not available.
 */
class LoggerTest extends TestCase {

	private Logger $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = new Logger( 'test-source' );
	}

	public function test_logger_accepts_log_calls_without_crashing(): void {
		$this->logger->debug( 'debug message' );
		$this->logger->info( 'info message' );
		$this->logger->warning( 'warning message' );
		$this->logger->error( 'error message' );

		// No assertion necessary — the test passes if no exception is thrown.
		$this->addToAssertionCount( 1 );
	}

	public function test_logger_accepts_context(): void {
		$this->logger->info( 'order processed', array( 'order_id' => 42 ) );

		$this->addToAssertionCount( 1 );
	}
}
