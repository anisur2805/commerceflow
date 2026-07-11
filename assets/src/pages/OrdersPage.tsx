/**
 * Orders page — workflow list with guarded transitions and per-order timeline
 * (v0.3 slice).
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner, Notice, Modal } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useToastContext } from '../components/AdminPage';

interface TransitionOption {
	slug: string;
	label: string;
}

interface Order {
	id: number;
	number: string;
	status: string;
	status_label: string;
	total: number;
	currency: string;
	customer: string;
	date_created: string;
	allowed_transitions: TransitionOption[];
}

interface TimelineEntry {
	source: string;
	type: string;
	actor: string;
	message: string;
	status: string;
	created_at: string;
}

function formatMoney( amount: number, currency: string ): string {
	return new Intl.NumberFormat( 'en-US', {
		style: 'currency',
		currency: currency || 'USD',
	} ).format( amount );
}

/**
 * Orders workflow page.
 */
export function OrdersPage() {
	const { addToast } = useToastContext();
	const [ orders, setOrders ] = useState< Order[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ busyId, setBusyId ] = useState< number | null >( null );
	const [ timelineOrder, setTimelineOrder ] = useState< Order | null >(
		null
	);

	const load = useCallback( () => {
		setIsLoading( true );
		setError( null );
		apiFetch< Order[] >( { path: 'commerceflow/v1/orders?per_page=30' } )
			.then( ( result ) => {
				setOrders( result );
				setIsLoading( false );
			} )
			.catch( ( err: Error ) => {
				setError( err?.message ?? 'Unknown error' );
				setIsLoading( false );
			} );
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const transition = useCallback(
		( order: Order, to: TransitionOption ) => {
			setBusyId( order.id );
			apiFetch< Order >( {
				path: `commerceflow/v1/orders/${ order.id }/transition`,
				method: 'POST',
				data: { to: to.slug },
			} )
				.then( () => {
					addToast(
						__( 'Order status updated.', 'commerceflow' ),
						'success'
					);
					setBusyId( null );
					load();
				} )
				.catch( ( err: Error ) => {
					addToast(
						err?.message ??
							__( 'Transition failed.', 'commerceflow' ),
						'error'
					);
					setBusyId( null );
				} );
		},
		[ addToast, load ]
	);

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __( 'Failed to load orders:', 'commerceflow' ) } { error }
			</Notice>
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
				{ __( 'Orders', 'commerceflow' ) }
			</h1>

			{ isLoading ? (
				<Spinner />
			) : (
				<div
					style={ {
						background: '#fff',
						borderRadius: '8px',
						padding: '20px',
						boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
					} }
				>
					{ orders.length === 0 ? (
						<p style={ { color: '#9ca3af' } }>
							{ __( 'No orders found.', 'commerceflow' ) }
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
									<th style={ headCell }>
										{ __( 'Order', 'commerceflow' ) }
									</th>
									<th style={ headCell }>
										{ __( 'Customer', 'commerceflow' ) }
									</th>
									<th style={ headCell }>
										{ __( 'Status', 'commerceflow' ) }
									</th>
									<th
										style={ {
											...headCell,
											textAlign: 'right',
										} }
									>
										{ __( 'Total', 'commerceflow' ) }
									</th>
									<th style={ headCell }>
										{ __( 'Actions', 'commerceflow' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ orders.map( ( order ) => (
									<tr
										key={ order.id }
										style={ {
											borderBottom: '1px solid #f3f4f6',
										} }
									>
										<td style={ bodyCell }>
											<button
												type="button"
												onClick={ () =>
													setTimelineOrder( order )
												}
												style={ linkButton }
											>
												#{ order.number }
											</button>
										</td>
										<td style={ bodyCell }>
											{ order.customer || '—' }
										</td>
										<td style={ bodyCell }>
											{ order.status_label }
										</td>
										<td
											style={ {
												...bodyCell,
												textAlign: 'right',
											} }
										>
											{ formatMoney(
												order.total,
												order.currency
											) }
										</td>
										<td style={ bodyCell }>
											<div
												style={ {
													display: 'flex',
													flexWrap: 'wrap',
													gap: '6px',
												} }
											>
												{ order.allowed_transitions
													.length === 0 ? (
													<span
														style={ {
															color: '#9ca3af',
															fontSize: '12px',
														} }
													>
														{ __(
															'—',
															'commerceflow'
														) }
													</span>
												) : (
													order.allowed_transitions.map(
														( option ) => (
															<Button
																key={
																	option.slug
																}
																variant="secondary"
																isSmall
																isBusy={
																	busyId ===
																	order.id
																}
																disabled={
																	busyId ===
																	order.id
																}
																onClick={ () =>
																	transition(
																		order,
																		option
																	)
																}
															>
																{ option.label }
															</Button>
														)
													)
												) }
											</div>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</div>
			) }

			{ timelineOrder && (
				<TimelineModal
					order={ timelineOrder }
					onClose={ () => setTimelineOrder( null ) }
				/>
			) }
		</div>
	);
}

/**
 * Per-order timeline modal.
 * @param root0
 * @param root0.order
 * @param root0.onClose
 */
function TimelineModal( {
	order,
	onClose,
}: {
	order: Order;
	onClose: () => void;
} ) {
	const [ entries, setEntries ] = useState< TimelineEntry[] >( [] );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		apiFetch< TimelineEntry[] >( {
			path: `commerceflow/v1/orders/${ order.id }/timeline`,
		} )
			.then( ( result ) => {
				setEntries( result );
				setLoading( false );
			} )
			.catch( () => setLoading( false ) );
	}, [ order.id ] );

	let body;
	if ( loading ) {
		body = <Spinner />;
	} else if ( entries.length === 0 ) {
		body = (
			<p style={ { color: '#9ca3af' } }>
				{ __( 'No timeline activity yet.', 'commerceflow' ) }
			</p>
		);
	} else {
		body = (
			<ul style={ { listStyle: 'none', margin: 0, padding: 0 } }>
				{ entries.map( ( entry, index ) => (
					<li
						key={ index }
						style={ {
							padding: '10px 0',
							borderBottom: '1px solid #f3f4f6',
						} }
					>
						<div
							style={ {
								fontSize: '13px',
								fontWeight: 600,
								color:
									entry.source === 'automation'
										? '#7c3aed'
										: '#111827',
							} }
						>
							{ entry.message }
						</div>
						<div
							style={ {
								fontSize: '12px',
								color: '#6b7280',
							} }
						>
							{ entry.actor } · { entry.created_at }
						</div>
					</li>
				) ) }
			</ul>
		);
	}

	return (
		<Modal
			title={ `${ __( 'Timeline — Order #', 'commerceflow' ) }${
				order.number
			}` }
			onRequestClose={ onClose }
		>
			{ body }
		</Modal>
	);
}

const headCell = {
	textAlign: 'left' as const,
	padding: '8px 4px',
	fontSize: '12px',
	color: '#6b7280',
	textTransform: 'uppercase' as const,
};

const bodyCell = {
	padding: '10px 4px',
	fontSize: '14px',
};

const linkButton = {
	background: 'none',
	border: 'none',
	color: '#2563eb',
	cursor: 'pointer',
	padding: 0,
	fontSize: '14px',
	textDecoration: 'underline',
};
