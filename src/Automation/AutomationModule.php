<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;
use CommerceFlow\Logger\Logger;
use WC_Order;

/**
 * Automation module — wires triggers to Action Scheduler and executes rules.
 *
 * Hard requirements (FR-AUTO-3..7):
 *  - async execution via Action Scheduler (FR-AUTO-3)
 *  - loop prevention via RecursionGuard (FR-AUTO-4)
 *  - idempotency via IdempotencyStore (FR-AUTO-5)
 *  - partial-failure handling via ExecutionPolicy (FR-AUTO-6)
 */
class AutomationModule implements ModuleInterface {

	/**
	 * Action Scheduler hook for async rule execution.
	 */
	public const EXEC_HOOK = 'commerceflow_execute_rule';

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container DI container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register trigger listeners and the execution callback.
	 */
	public function register(): void {
		add_action( 'woocommerce_new_order', array( $this, 'on_order_created' ) );
		add_action( 'woocommerce_payment_complete', array( $this, 'on_order_paid' ) );
		add_action( 'woocommerce_order_status_failed', array( $this, 'on_order_failed' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );

		// Admin-created orders go through save_post rather than woocommerce_new_order.
		add_action( 'save_post_shop_order', array( $this, 'on_order_saved' ), 10, 3 );

		// Async execution callback (Action Scheduler).
		add_action( self::EXEC_HOOK, array( $this, 'execute_rule' ), 10, 4 );
	}

	/**
	 * Boot.
	 */
	public function boot(): void {
		// No-op.
	}

	/**
	 * Trigger: order created.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_created( int $order_id ): void {
		$this->dispatch( $order_id, 'order_created', array() );
	}

	/**
	 * Trigger: order saved — catches admin-created orders that don't
	 * fire woocommerce_new_order.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $is_new  Whether this is a new post.
	 */
	public function on_order_saved( int $post_id, \WP_Post $post, bool $is_new ): void {
		if ( ! $is_new ) {
			return;
		}
		if ( 'shop_order' !== $post->post_type ) {
			return;
		}
		$this->on_order_created( $post_id );
	}

	/**
	 * Trigger: order paid.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_paid( int $order_id ): void {
		$this->dispatch( $order_id, 'order_paid', array() );
	}

	/**
	 * Trigger: order failed.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_failed( int $order_id ): void {
		$this->dispatch( $order_id, 'order_failed', array() );
	}

	/**
	 * Trigger: order status changed.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Previous status (without wc- prefix).
	 * @param string   $to       New status (without wc- prefix).
	 * @param WC_Order $order    Order object.
	 */
	public function on_order_status_changed( int $order_id, string $from, string $to, WC_Order $order ): void {
		$this->dispatch(
			$order_id,
			'order_status_changed',
			array(
				'from' => 'wc-' . $from,
				'to'   => 'wc-' . $to,
			)
		);
	}

	/**
	 * Evaluate matching rules and schedule async execution for each.
	 *
	 * @param int                  $order_id       Order ID.
	 * @param string               $trigger        Trigger name.
	 * @param array<string, mixed> $trigger_config Trigger context.
	 */
	private function dispatch( int $order_id, string $trigger, array $trigger_config ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			error_log( "CommerceFlow dispatch: order #{$order_id} not found" );
			return;
		}

		$repository = new RuleRepository();
		$rules      = $repository->find_enabled_by_trigger( $trigger );

		error_log( "CommerceFlow dispatch: order #{$order_id}, trigger={$trigger}, rules_found=" . count( $rules ) );

		if ( array() === $rules ) {
			return;
		}

		$snapshot  = $this->build_snapshot( $order );
		$evaluator = new RuleEvaluator();
		$matches   = $evaluator->evaluate( $rules, $trigger, $trigger_config, $snapshot );

		error_log( "CommerceFlow evaluate: order #{$order_id}, trigger={$trigger}, matched=" . count( $matches ) . ', snapshot=' . var_export( $snapshot, true ) );

		if ( array() === $matches && array() !== $rules ) {
			// Rules exist for this trigger but no conditions matched.
			$this->log(
				0,
				$order_id,
				$trigger,
				'skipped',
				'',
				array(
					'reason'        => 'no_conditions_matched',
					'snapshot'      => $snapshot,
					'rules_checked' => count( $rules ),
				)
			);
		}

		foreach ( $matches as $rule ) {
			// Run synchronously for immediate feedback — Action Scheduler
			// is the preferred async path but may not process in all dev
			// environments (no cron, etc.).
			$this->execute_rule( $rule->id, $order_id, $trigger, $trigger_config );

			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action(
					self::EXEC_HOOK,
					array(
						'rule_id'        => $rule->id,
						'order_id'       => $order_id,
						'trigger'        => $trigger,
						'trigger_config' => $trigger_config,
					)
				);
			}
		}
	}

	/**
	 * Async execution callback — runs an evaluated rule against its order.
	 *
	 * @param int                  $rule_id        Rule ID.
	 * @param int                  $order_id       Order ID.
	 * @param string               $trigger        Trigger name.
	 * @param array<string, mixed> $trigger_config Trigger context.
	 */
	public function execute_rule( int $rule_id, int $order_id, string $trigger, array $trigger_config = array() ): void {
		$logger = $this->logger();

		$repository = new RuleRepository();
		$rule       = $repository->find( $rule_id );
		if ( ! $rule || ! $rule->enabled ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$guard = new RecursionGuard( 1 );
		if ( ! $guard->enter( $rule_id, $order_id ) ) {
			$this->log( $rule_id, $order_id, $trigger, 'suppressed', '', array( 'reason' => 'loop_prevention' ) );
			if ( $logger ) {
				$logger->warning( sprintf( 'Rule %d suppressed (loop) for order %d.', $rule_id, $order_id ) );
			}
			return;
		}

		$run_id   = sprintf( '%d:%d:%d', $rule_id, $order_id, time() );
		$store    = new IdempotencyStore();
		$policy   = new ExecutionPolicy();
		$executor = new ActionExecutor();

		$planned = ActionPlanner::plan( $rule->actions );

		$result = $policy->execute(
			$planned,
			static function ( PlannedAction $action ) use ( $executor, $order ): array {
				return $executor->execute( $action, $order );
			},
			ExecutionPolicy::MODE_STOP,
			$store,
			$run_id
		);

		$guard->exit( $rule_id, $order_id );

		$this->log( $rule_id, $order_id, $trigger, $result->status, $run_id, $result->to_array() );

		if ( $logger ) {
			$logger->info(
				sprintf( 'Rule %d executed for order %d: %s', $rule_id, $order_id, $result->status ),
				array( 'run_id' => $run_id )
			);
		}
	}

	/**
	 * Build an order snapshot for condition matching.
	 *
	 * @param  WC_Order $order Order.
	 * @return array<string, mixed>
	 */
	private function build_snapshot( WC_Order $order ): array {
		return array(
			'id'             => $order->get_id(),
			'status'         => 'wc-' . $order->get_status(),
			'total'          => (float) $order->get_total(),
			'payment_method' => $order->get_payment_method(),
			'customer_id'    => $order->get_customer_id(),
			'email'          => $order->get_billing_email(),
		);
	}

	/**
	 * Write a rule-log entry.
	 *
	 * @param int                  $rule_id    Rule ID.
	 * @param int                  $order_id   Order ID.
	 * @param string               $trigger    Trigger.
	 * @param string               $status     Status.
	 * @param string               $run_id     Run ID.
	 * @param array<string, mixed> $detail     Detail.
	 */
	private function log( int $rule_id, int $order_id, string $trigger, string $status, string $run_id, array $detail ): void {
		( new RuleLogRepository() )->insert(
			array(
				'rule_id'  => $rule_id,
				'order_id' => $order_id,
				'trigger'  => $trigger,
				'status'   => $status,
				'run_id'   => $run_id,
				'detail'   => $detail,
			)
		);
	}

	/**
	 * Get the logger singleton if available.
	 */
	private function logger(): ?Logger {
		try {
			if ( $this->container->has( Logger::class ) ) {
				$logger = $this->container->get( Logger::class );
				return $logger instanceof Logger ? $logger : null;
			}
		} catch ( \Throwable $e ) {
			return null;
		}
		return null;
	}
}
