<?php

declare(strict_types=1);

namespace CommerceFlow\Logger;

/**
 * Simple structured logger that writes to WooCommerce logs via WC_Logger.
 */
class Logger {

	/**
	 * WC_Logger instance.
	 *
	 * @var \WC_Logger|null
	 */
	private $logger;

	/**
	 * Logging source / context.
	 *
	 * @var string
	 */
	private string $source;

	/**
	 * Constructor.
	 *
	 * @param string $source Context label for log entries.
	 */
	public function __construct( string $source = 'commerceflow' ) {
		$this->source = $source;
	}

	/**
	 * Lazily initialise the WC_Logger.
	 */
	private function ensure_logger(): void {
		if ( null === $this->logger && function_exists( 'wc_get_logger' ) ) {
			$this->logger = wc_get_logger();
		}
	}

	/**
	 * Log at DEBUG level.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->ensure_logger();
		if ( $this->logger ) {
			$this->logger->debug( $message, array_merge( array( 'source' => $this->source ), $context ) );
		}
	}

	/**
	 * Log at INFO level.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	public function info( string $message, array $context = array() ): void {
		$this->ensure_logger();
		if ( $this->logger ) {
			$this->logger->info( $message, array_merge( array( 'source' => $this->source ), $context ) );
		}
	}

	/**
	 * Log at WARNING level.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->ensure_logger();
		if ( $this->logger ) {
			$this->logger->warning( $message, array_merge( array( 'source' => $this->source ), $context ) );
		}
	}

	/**
	 * Log at ERROR level.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	public function error( string $message, array $context = array() ): void {
		$this->ensure_logger();
		if ( $this->logger ) {
			$this->logger->error( $message, array_merge( array( 'source' => $this->source ), $context ) );
		}
	}
}
