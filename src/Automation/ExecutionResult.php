<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Result of executing a rule's actions — per-action status + overall status.
 */
class ExecutionResult {

	/** @var string */
	public string $status;

	/** @var array<int, array{type: string, status: string, message: string}> */
	public array $actions;

	/**
	 * Constructor.
	 *
	 * @param string                                                          $status  Overall status.
	 * @param array<int, array{type: string, status: string, message: string}> $actions Per-action results.
	 */
	public function __construct( string $status, array $actions ) {
		$this->status  = $status;
		$this->actions = $actions;
	}

	/**
	 * @return array{status: string, actions: array<int, array{type: string, status: string, message: string}>}
	 */
	public function to_array(): array {
		return array(
			'status'  => $this->status,
			'actions' => $this->actions,
		);
	}
}
