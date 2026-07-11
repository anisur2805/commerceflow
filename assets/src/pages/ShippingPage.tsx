/**
 * Shipping page — rule-based shipping CRUD, priority-ordered, with a preview
 * tool (v0.4 slice).
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Spinner,
	Notice,
	Modal,
	TextControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useToastContext } from '../components/AdminPage';

interface Condition {
	field: string;
	operator: string;
	value: string;
}

interface Rate {
	label: string;
	cost: number;
}

interface ShippingRule {
	id: number;
	name: string;
	conditions: Condition[];
	rate: Rate;
	enabled: boolean;
	priority: number;
}

const FIELDS = [
	'country',
	'state',
	'postcode',
	'weight',
	'subtotal',
	'category',
	'shipping_class',
	'coupon',
];

const OPERATORS = [ 'eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'contains' ];

function emptyRule(): ShippingRule {
	return {
		id: 0,
		name: '',
		conditions: [],
		rate: { label: '', cost: 0 },
		enabled: true,
		priority: 0,
	};
}

/**
 * Convert an editor condition into a stored condition, splitting comma lists
 * for the `in` operator so the backend receives an array.
 * @param condition Editor condition row.
 */
function serializeCondition( condition: Condition ) {
	const value =
		condition.operator === 'in'
			? condition.value
					.split( ',' )
					.map( ( part ) => part.trim() )
					.filter( Boolean )
			: condition.value;
	return { ...condition, value };
}

/**
 * Shipping rules page.
 */
export function ShippingPage() {
	const { addToast } = useToastContext();
	const [ rules, setRules ] = useState< ShippingRule[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ editing, setEditing ] = useState< ShippingRule | null >( null );

	const load = useCallback( () => {
		setIsLoading( true );
		setError( null );
		apiFetch< ShippingRule[] >( { path: 'commerceflow/v1/shipping' } )
			.then( ( result ) => {
				setRules( result );
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

	const remove = useCallback(
		( rule: ShippingRule ) => {
			apiFetch( {
				path: `commerceflow/v1/shipping/${ rule.id }`,
				method: 'DELETE',
			} )
				.then( () => {
					addToast(
						__( 'Shipping rule deleted.', 'commerceflow' ),
						'success'
					);
					load();
				} )
				.catch( ( err: Error ) =>
					addToast(
						err?.message ?? __( 'Delete failed.', 'commerceflow' ),
						'error'
					)
				);
		},
		[ addToast, load ]
	);

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __( 'Failed to load shipping rules:', 'commerceflow' ) }{ ' ' }
				{ error }
			</Notice>
		);
	}

	return (
		<div>
			<div
				style={ {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
					margin: '0 0 24px',
				} }
			>
				<h1
					style={ {
						margin: 0,
						fontSize: '24px',
						fontWeight: 600,
						color: '#111827',
					} }
				>
					{ __( 'Shipping', 'commerceflow' ) }
				</h1>
				<Button
					variant="primary"
					onClick={ () => setEditing( emptyRule() ) }
				>
					{ __( 'Add rule', 'commerceflow' ) }
				</Button>
			</div>

			<PreviewTool />

			{ isLoading ? (
				<Spinner />
			) : (
				<div
					style={ {
						background: '#fff',
						borderRadius: '8px',
						padding: '20px',
						boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
						marginTop: '24px',
					} }
				>
					{ rules.length === 0 ? (
						<p style={ { color: '#9ca3af' } }>
							{ __( 'No shipping rules yet.', 'commerceflow' ) }
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
										{ __( 'Priority', 'commerceflow' ) }
									</th>
									<th style={ headCell }>
										{ __( 'Name', 'commerceflow' ) }
									</th>
									<th style={ headCell }>
										{ __( 'Rate', 'commerceflow' ) }
									</th>
									<th style={ headCell }>
										{ __( 'Status', 'commerceflow' ) }
									</th>
									<th style={ headCell }>
										{ __( 'Actions', 'commerceflow' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ rules.map( ( rule ) => (
									<tr
										key={ rule.id }
										style={ {
											borderBottom: '1px solid #f3f4f6',
										} }
									>
										<td style={ bodyCell }>
											{ rule.priority }
										</td>
										<td style={ bodyCell }>
											{ rule.name }
										</td>
										<td style={ bodyCell }>
											{ rule.rate.label } (
											{ rule.rate.cost })
										</td>
										<td style={ bodyCell }>
											{ rule.enabled
												? __(
														'Enabled',
														'commerceflow'
												  )
												: __(
														'Disabled',
														'commerceflow'
												  ) }
										</td>
										<td style={ bodyCell }>
											<Button
												variant="secondary"
												isSmall
												onClick={ () =>
													setEditing( rule )
												}
											>
												{ __( 'Edit', 'commerceflow' ) }
											</Button>{ ' ' }
											<Button
												variant="tertiary"
												isSmall
												isDestructive
												onClick={ () => remove( rule ) }
											>
												{ __(
													'Delete',
													'commerceflow'
												) }
											</Button>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</div>
			) }

			{ editing && (
				<RuleEditor
					rule={ editing }
					onClose={ () => setEditing( null ) }
					onSaved={ () => {
						setEditing( null );
						load();
					} }
				/>
			) }
		</div>
	);
}

/**
 * Create/edit rule modal.
 * @param root0
 * @param root0.rule
 * @param root0.onClose
 * @param root0.onSaved
 */
function RuleEditor( {
	rule,
	onClose,
	onSaved,
}: {
	rule: ShippingRule;
	onClose: () => void;
	onSaved: () => void;
} ) {
	const { addToast } = useToastContext();
	const [ draft, setDraft ] = useState< ShippingRule >( rule );
	const [ saving, setSaving ] = useState( false );

	const addCondition = () =>
		setDraft( ( prev ) => ( {
			...prev,
			conditions: [
				...prev.conditions,
				{ field: 'country', operator: 'eq', value: '' },
			],
		} ) );

	const updateCondition = ( index: number, patch: Partial< Condition > ) =>
		setDraft( ( prev ) => ( {
			...prev,
			conditions: prev.conditions.map( ( condition, i ) =>
				i === index ? { ...condition, ...patch } : condition
			),
		} ) );

	const removeCondition = ( index: number ) =>
		setDraft( ( prev ) => ( {
			...prev,
			conditions: prev.conditions.filter( ( _, i ) => i !== index ),
		} ) );

	const save = () => {
		setSaving( true );
		const payload = {
			name: draft.name,
			priority: draft.priority,
			enabled: draft.enabled,
			rate: draft.rate,
			conditions: draft.conditions.map( serializeCondition ),
		};
		const path = draft.id
			? `commerceflow/v1/shipping/${ draft.id }`
			: 'commerceflow/v1/shipping';
		apiFetch( { path, method: draft.id ? 'PUT' : 'POST', data: payload } )
			.then( () => {
				addToast(
					__( 'Shipping rule saved.', 'commerceflow' ),
					'success'
				);
				onSaved();
			} )
			.catch( ( err: Error ) => {
				addToast(
					err?.message ?? __( 'Save failed.', 'commerceflow' ),
					'error'
				);
				setSaving( false );
			} );
	};

	return (
		<Modal
			title={
				draft.id
					? __( 'Edit shipping rule', 'commerceflow' )
					: __( 'Add shipping rule', 'commerceflow' )
			}
			onRequestClose={ onClose }
		>
			<TextControl
				label={ __( 'Name', 'commerceflow' ) }
				value={ draft.name }
				onChange={ ( value ) =>
					setDraft( ( prev ) => ( { ...prev, name: value } ) )
				}
			/>
			<TextControl
				label={ __( 'Priority', 'commerceflow' ) }
				type="number"
				value={ String( draft.priority ) }
				onChange={ ( value ) =>
					setDraft( ( prev ) => ( {
						...prev,
						priority: Number( value ) || 0,
					} ) )
				}
			/>
			<ToggleControl
				label={ __( 'Enabled', 'commerceflow' ) }
				checked={ draft.enabled }
				onChange={ ( value ) =>
					setDraft( ( prev ) => ( { ...prev, enabled: value } ) )
				}
			/>

			<h3>{ __( 'Rate', 'commerceflow' ) }</h3>
			<TextControl
				label={ __( 'Label', 'commerceflow' ) }
				value={ draft.rate.label }
				onChange={ ( value ) =>
					setDraft( ( prev ) => ( {
						...prev,
						rate: { ...prev.rate, label: value },
					} ) )
				}
			/>
			<TextControl
				label={ __( 'Cost', 'commerceflow' ) }
				type="number"
				value={ String( draft.rate.cost ) }
				onChange={ ( value ) =>
					setDraft( ( prev ) => ( {
						...prev,
						rate: { ...prev.rate, cost: Number( value ) || 0 },
					} ) )
				}
			/>

			<h3>{ __( 'Conditions', 'commerceflow' ) }</h3>
			{ draft.conditions.map( ( condition, index ) => (
				<div
					key={ index }
					style={ {
						display: 'flex',
						gap: '8px',
						alignItems: 'flex-end',
						marginBottom: '8px',
					} }
				>
					<SelectControl
						label={ __( 'Field', 'commerceflow' ) }
						value={ condition.field }
						options={ FIELDS.map( ( field ) => ( {
							label: field,
							value: field,
						} ) ) }
						onChange={ ( value ) =>
							updateCondition( index, { field: value } )
						}
					/>
					<SelectControl
						label={ __( 'Operator', 'commerceflow' ) }
						value={ condition.operator }
						options={ OPERATORS.map( ( operator ) => ( {
							label: operator,
							value: operator,
						} ) ) }
						onChange={ ( value ) =>
							updateCondition( index, { operator: value } )
						}
					/>
					<TextControl
						label={ __( 'Value', 'commerceflow' ) }
						value={ condition.value }
						onChange={ ( value ) =>
							updateCondition( index, { value } )
						}
					/>
					<Button
						variant="tertiary"
						isDestructive
						onClick={ () => removeCondition( index ) }
					>
						{ __( 'Remove', 'commerceflow' ) }
					</Button>
				</div>
			) ) }
			<Button variant="secondary" onClick={ addCondition }>
				{ __( 'Add condition', 'commerceflow' ) }
			</Button>

			<div style={ { marginTop: '24px' } }>
				<Button
					variant="primary"
					isBusy={ saving }
					disabled={ saving }
					onClick={ save }
				>
					{ __( 'Save', 'commerceflow' ) }
				</Button>{ ' ' }
				<Button variant="tertiary" onClick={ onClose }>
					{ __( 'Cancel', 'commerceflow' ) }
				</Button>
			</div>
		</Modal>
	);
}

interface PreviewResult {
	matched: boolean;
	rate: { label: string; cost: number; name: string } | null;
}

/**
 * Preview tool — resolve a sample package against current rules, no side
 * effects.
 */
function PreviewTool() {
	const [ sample, setSample ] = useState( {
		country: '',
		state: '',
		postcode: '',
		weight: '',
		subtotal: '',
	} );
	const [ result, setResult ] = useState< PreviewResult | null >( null );
	const [ running, setRunning ] = useState( false );

	const run = () => {
		setRunning( true );
		apiFetch< PreviewResult >( {
			path: 'commerceflow/v1/shipping/preview',
			method: 'POST',
			data: {
				sample: {
					country: sample.country,
					state: sample.state,
					postcode: sample.postcode,
					weight: Number( sample.weight ) || 0,
					subtotal: Number( sample.subtotal ) || 0,
				},
			},
		} )
			.then( ( response ) => {
				setResult( response );
				setRunning( false );
			} )
			.catch( () => setRunning( false ) );
	};

	return (
		<div
			style={ {
				background: '#f9fafb',
				border: '1px solid #e5e7eb',
				borderRadius: '8px',
				padding: '16px',
			} }
		>
			<h2
				style={ {
					margin: '0 0 12px',
					fontSize: '15px',
					fontWeight: 600,
				} }
			>
				{ __( 'Preview', 'commerceflow' ) }
			</h2>
			<div
				style={ {
					display: 'flex',
					gap: '8px',
					flexWrap: 'wrap',
					alignItems: 'flex-end',
				} }
			>
				<TextControl
					label={ __( 'Country', 'commerceflow' ) }
					value={ sample.country }
					onChange={ ( value ) =>
						setSample( ( prev ) => ( { ...prev, country: value } ) )
					}
				/>
				<TextControl
					label={ __( 'State', 'commerceflow' ) }
					value={ sample.state }
					onChange={ ( value ) =>
						setSample( ( prev ) => ( { ...prev, state: value } ) )
					}
				/>
				<TextControl
					label={ __( 'Postcode', 'commerceflow' ) }
					value={ sample.postcode }
					onChange={ ( value ) =>
						setSample( ( prev ) => ( {
							...prev,
							postcode: value,
						} ) )
					}
				/>
				<TextControl
					label={ __( 'Weight', 'commerceflow' ) }
					type="number"
					value={ sample.weight }
					onChange={ ( value ) =>
						setSample( ( prev ) => ( { ...prev, weight: value } ) )
					}
				/>
				<TextControl
					label={ __( 'Subtotal', 'commerceflow' ) }
					type="number"
					value={ sample.subtotal }
					onChange={ ( value ) =>
						setSample( ( prev ) => ( {
							...prev,
							subtotal: value,
						} ) )
					}
				/>
				<Button
					variant="secondary"
					isBusy={ running }
					disabled={ running }
					onClick={ run }
				>
					{ __( 'Run preview', 'commerceflow' ) }
				</Button>
			</div>
			{ result && (
				<p style={ { marginTop: '12px', fontSize: '14px' } }>
					{ result.matched && result.rate
						? `${ __( 'Matched:', 'commerceflow' ) } ${
								result.rate.name
						  } — ${ result.rate.label } (${ result.rate.cost })`
						: __( 'No rule matched.', 'commerceflow' ) }
				</p>
			) }
		</div>
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
