/**
 * Dashboard page — shows WooCommerce-native operational metrics.
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useApiFetch } from '../hooks/useApiFetch';

interface DashboardData {
	orders_today: number;
	revenue_today: number;
	pending_orders: number;
	failed_payments: number;
	revenue_30d: Array< { date: string; revenue: number } >;
	top_products_30d: Array< {
		id: number;
		name: string;
		quantity: number;
		total: number;
	} >;
	fulfillment: Array< { status: string; label: string; count: number } >;
}

interface AutomationLog {
	id: number;
	rule_id: number;
	order_id: number;
	trigger: string;
	status: string;
	run_id: string;
	detail: unknown;
	created_at: string;
}

function formatCurrency( amount: number ): string {
	return new Intl.NumberFormat( 'en-US', {
		style: 'currency',
		currency: 'USD',
	} ).format( amount );
}

/**
 * Stat card component.
 * @param root0
 * @param root0.title
 * @param root0.value
 * @param root0.loading
 * @param root0.error
 */
function StatCard( {
	title,
	value,
	loading,
	error,
}: {
	title: string;
	value: string;
	loading: boolean;
	error: boolean;
} ) {
	return (
		<div
			style={ {
				background: '#fff',
				borderRadius: '8px',
				padding: '20px',
				boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
				flex: '1 1 200px',
				minWidth: '180px',
			} }
		>
			<h3
				style={ {
					margin: '0 0 8px',
					fontSize: '13px',
					color: '#6b7280',
					textTransform: 'uppercase',
					letterSpacing: '0.5px',
				} }
			>
				{ title }
			</h3>
			{ error ? (
				<p style={ { color: '#dc2626', fontSize: '13px' } }>
					{ __( 'Error loading', 'commerceflow' ) }
				</p>
			) : (
				<p
					style={ {
						margin: 0,
						fontSize: '28px',
						fontWeight: 700,
						color: '#111827',
					} }
				>
					{ loading ? '—' : value }
				</p>
			) }
		</div>
	);
}

/**
 * Dashboard page component.
 */
export function DashboardPage() {
	const { data, isLoading, error } = useApiFetch< DashboardData >(
		'commerceflow/v1/dashboard'
	);

	if ( error ) {
		return (
			<div style={ { padding: '40px', textAlign: 'center' } }>
				<p style={ { color: '#dc2626', fontSize: '16px' } }>
					{ __( 'Failed to load dashboard data.', 'commerceflow' ) }
				</p>
				<p style={ { color: '#6b7280', fontSize: '14px' } }>
					{ error }
				</p>
			</div>
		);
	}

	return (
		<div>
			<h1
				style={ {
					margin: '0 0 24px',
					fontSize: '24px',
					fontWeight: 600,
					color: '#111827',
				} }
			>
				{ __( 'Dashboard', 'commerceflow' ) }
			</h1>

			<div
				style={ {
					display: 'flex',
					flexWrap: 'wrap',
					gap: '16px',
					marginBottom: '32px',
				} }
			>
				<StatCard
					title={ __( 'Orders Today', 'commerceflow' ) }
					value={ String( data?.orders_today ?? 0 ) }
					loading={ isLoading }
					error={ false }
				/>
				<StatCard
					title={ __( 'Revenue Today', 'commerceflow' ) }
					value={ formatCurrency( data?.revenue_today ?? 0 ) }
					loading={ isLoading }
					error={ false }
				/>
				<StatCard
					title={ __( 'Pending Orders', 'commerceflow' ) }
					value={ String( data?.pending_orders ?? 0 ) }
					loading={ isLoading }
					error={ false }
				/>
				<StatCard
					title={ __( 'Failed Payments', 'commerceflow' ) }
					value={ String( data?.failed_payments ?? 0 ) }
					loading={ isLoading }
					error={ false }
				/>
			</div>

			<div
				style={ {
					display: 'grid',
					gridTemplateColumns: '1fr 1fr',
					gap: '24px',
				} }
			>
				{ /* Revenue chart placeholder */ }
				<div
					style={ {
						background: '#fff',
						borderRadius: '8px',
						padding: '20px',
						boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
					} }
				>
					<h2
						style={ {
							margin: '0 0 16px',
							fontSize: '16px',
							fontWeight: 600,
							color: '#111827',
						} }
					>
						{ __( '30-Day Revenue', 'commerceflow' ) }
					</h2>
					{ isLoading ? (
						<p style={ { color: '#9ca3af' } }>
							{ __( 'Loading…', 'commerceflow' ) }
						</p>
					) : (
						<RevenueChart data={ data?.revenue_30d ?? [] } />
					) }
				</div>

				{ /* Top products list */ }
				<div
					style={ {
						background: '#fff',
						borderRadius: '8px',
						padding: '20px',
						boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
					} }
				>
					<h2
						style={ {
							margin: '0 0 16px',
							fontSize: '16px',
							fontWeight: 600,
							color: '#111827',
						} }
					>
						{ __( 'Top Products (30 days)', 'commerceflow' ) }
					</h2>
					{ isLoading ? (
						<p style={ { color: '#9ca3af' } }>
							{ __( 'Loading…', 'commerceflow' ) }
						</p>
					) : (
						<table
							style={ {
								width: '100%',
								borderCollapse: 'collapse',
							} }
						>
							<thead>
								<tr
									style={ {
										borderBottom: '2px solid #e5e7eb',
									} }
								>
									<th
										style={ {
											textAlign: 'left',
											padding: '8px 4px',
											fontSize: '12px',
											color: '#6b7280',
											textTransform: 'uppercase',
										} }
									>
										{ __( 'Product', 'commerceflow' ) }
									</th>
									<th
										style={ {
											textAlign: 'right',
											padding: '8px 4px',
											fontSize: '12px',
											color: '#6b7280',
											textTransform: 'uppercase',
										} }
									>
										{ __( 'Sold', 'commerceflow' ) }
									</th>
									<th
										style={ {
											textAlign: 'right',
											padding: '8px 4px',
											fontSize: '12px',
											color: '#6b7280',
											textTransform: 'uppercase',
										} }
									>
										{ __( 'Revenue', 'commerceflow' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ data?.top_products_30d.map( ( product ) => (
									<tr
										key={ product.id }
										style={ {
											borderBottom: '1px solid #f3f4f6',
										} }
									>
										<td
											style={ {
												padding: '10px 4px',
												fontSize: '14px',
											} }
										>
											{ product.name }
										</td>
										<td
											style={ {
												padding: '10px 4px',
												fontSize: '14px',
												textAlign: 'right',
											} }
										>
											{ product.quantity }
										</td>
										<td
											style={ {
												padding: '10px 4px',
												fontSize: '14px',
												textAlign: 'right',
											} }
										>
											{ formatCurrency( product.total ) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</div>
			</div>

			<div
				style={ {
					display: 'grid',
					gridTemplateColumns: '1fr 1fr',
					gap: '24px',
					marginTop: '24px',
				} }
			>
				<FulfillmentCard
					data={ data?.fulfillment ?? [] }
					loading={ isLoading }
				/>
				<AutomationQueueCard />
			</div>
		</div>
	);
}

/**
 * Fulfillment card — open orders per custom workflow status (v0.3 slice).
 * @param root0
 * @param root0.data
 * @param root0.loading
 */
function FulfillmentCard( {
	data,
	loading,
}: {
	data: Array< { status: string; label: string; count: number } >;
	loading: boolean;
} ) {
	return (
		<div
			style={ {
				background: '#fff',
				borderRadius: '8px',
				padding: '20px',
				boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
			} }
		>
			<h2
				style={ {
					margin: '0 0 16px',
					fontSize: '16px',
					fontWeight: 600,
					color: '#111827',
				} }
			>
				{ __( 'Fulfillment', 'commerceflow' ) }
			</h2>
			{ loading ? (
				<p style={ { color: '#9ca3af' } }>
					{ __( 'Loading…', 'commerceflow' ) }
				</p>
			) : (
				<table style={ { width: '100%', borderCollapse: 'collapse' } }>
					<tbody>
						{ data.map( ( row ) => (
							<tr
								key={ row.status }
								style={ { borderBottom: '1px solid #f3f4f6' } }
							>
								<td
									style={ {
										padding: '8px 4px',
										fontSize: '13px',
									} }
								>
									{ row.label }
								</td>
								<td
									style={ {
										padding: '8px 4px',
										fontSize: '14px',
										textAlign: 'right',
										fontWeight: 700,
										color: '#111827',
									} }
								>
									{ row.count }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</div>
	);
}

/**
 * Automation Queue card — recent automation rule executions (v0.2 slice).
 */
function AutomationQueueCard() {
	const { data, isLoading, error } = useApiFetch< AutomationLog[] >(
		'commerceflow/v1/automation/logs?per_page=8'
	);

	const statusColor: Record< string, string > = {
		success: '#16a34a',
		partial_failure: '#d97706',
		failed: '#dc2626',
		dry_run: '#6b7280',
		suppressed: '#6b7280',
	};

	let body;
	if ( error ) {
		body = (
			<p style={ { color: '#dc2626', fontSize: '13px' } }>
				{ __( 'Error loading', 'commerceflow' ) }
			</p>
		);
	} else if ( isLoading ) {
		body = (
			<p style={ { color: '#9ca3af' } }>
				{ __( 'Loading…', 'commerceflow' ) }
			</p>
		);
	} else if ( ( data?.length ?? 0 ) === 0 ) {
		body = (
			<p style={ { color: '#9ca3af', fontSize: '13px' } }>
				{ __( 'No recent automation runs.', 'commerceflow' ) }
			</p>
		);
	} else {
		body = (
			<table style={ { width: '100%', borderCollapse: 'collapse' } }>
				<tbody>
					{ data?.map( ( log ) => (
						<tr
							key={ log.id }
							style={ { borderBottom: '1px solid #f3f4f6' } }
						>
							<td
								style={ {
									padding: '8px 4px',
									fontSize: '13px',
								} }
							>
								#{ log.rule_id } ·{ ' ' }
								{ log.trigger.replace( /_/g, ' ' ) }
							</td>
							<td
								style={ {
									padding: '8px 4px',
									fontSize: '12px',
									textAlign: 'right',
									color:
										statusColor[ log.status ] ?? '#6b7280',
									fontWeight: 600,
								} }
							>
								{ log.status.replace( /_/g, ' ' ) }
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		);
	}

	return (
		<div
			style={ {
				background: '#fff',
				borderRadius: '8px',
				padding: '20px',
				boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
			} }
		>
			<h2
				style={ {
					margin: '0 0 16px',
					fontSize: '16px',
					fontWeight: 600,
					color: '#111827',
				} }
			>
				{ __( 'Automation Queue', 'commerceflow' ) }
			</h2>
			{ body }
		</div>
	);
}

/**
 * Simple bar chart for 30-day revenue.
 * @param root0
 * @param root0.data
 */
function RevenueChart( {
	data,
}: {
	data: Array< { date: string; revenue: number } >;
} ) {
	const maxRevenue = Math.max( ...data.map( ( d ) => d.revenue ), 1 );
	const barHeight = 160;

	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'flex-end',
				gap: '2px',
				height: `${ barHeight + 40 }px`,
				padding: '0 4px',
			} }
			role="img"
			aria-label={ __( '30-day revenue bar chart', 'commerceflow' ) }
		>
			{ data.map( ( day ) => {
				const height = Math.max(
					( day.revenue / maxRevenue ) * barHeight,
					2
				);
				return (
					<div
						key={ day.date }
						style={ {
							flex: 1,
							height: `${ height }px`,
							background: '#89b4fa',
							borderRadius: '2px 2px 0 0',
							position: 'relative',
						} }
						title={ `${ day.date }: ${ formatCurrency(
							day.revenue
						) }` }
					/>
				);
			} ) }
		</div>
	);
}
