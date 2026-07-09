<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * A normalized, executable action derived from a rule's action spec.
 *
 * Carries a stable hash so the idempotency store can detect retries.
 */
class PlannedAction {

	public string $type;

	/** @var array<string, mixed> */
	public array $config;

	/**
	 * Constructor.
	 *
	 * @param string               $type   Action type.
	 * @param array<string, mixed> $config Action config.
	 */
	public function __construct( string $type, array $config ) {
		$this->type   = $type;
		$this->config = $config;
	}

	/**
	 * Return a stable hash identifying this action + config.
	 *
	 * @return string
	 */
	public function hash(): string {
		return md5( $this->type . ':' . self::encode( $this->config ) );
	}

	/**
	 * Encode config to a stable string without depending on WordPress.
	 *
	 * @param  array<string, mixed> $config Config.
	 * @return string
	 */
	private static function encode( array $config ): string {
		$json = json_encode( $config );
		return false === $json ? serialize( $config ) : $json;
	}
}
