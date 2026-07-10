/**
 * Automation page — rule builder (create/edit/enable/disable/delete/dry-run).
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
	TextControl,
	SelectControl,
	ToggleControl,
	TextareaControl,
	Modal,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useToastContext } from '../components/AdminPage';

interface Condition {
	field: string;
	operator: string;
	value: string | number | string[];
}

interface Action {
	type: string;
	config: Record< string, unknown >;
}

interface Rule {
	id: number;
	name: string;
	trigger: string;
	trigger_config: Record< string, string >;
	conditions: Condition[];
	actions: Action[];
	enabled: boolean;
	priority: number;
	created_at: string;
	updated_at: string;
}

const TRIGGERS = [
	{ value: 'order_created', label: __( 'Order Created', 'commerceflow' ) },
	{ value: 'order_paid', label: __( 'Order Paid', 'commerceflow' ) },
	{ value: 'order_failed', label: __( 'Order Failed', 'commerceflow' ) },
	{
		value: 'order_status_changed',
		label: __( 'Order Status Changed', 'commerceflow' ),
	},
];

const ACTION_TYPES = [
	{
		value: 'change_status',
		label: __( 'Change Status', 'commerceflow' ),
	},
	{
		value: 'add_order_note',
		label: __( 'Add Order Note', 'commerceflow' ),
	},
	{
		value: 'generate_coupon',
		label: __( 'Generate Coupon', 'commerceflow' ),
	},
	{ value: 'call_webhook', label: __( 'Call Webhook', 'commerceflow' ) },
];

const OPERATORS = [
	{ value: 'eq', label: '= (equals)' },
	{ value: 'neq', label: '!= (not equals)' },
	{ value: 'gt', label: '> (greater than)' },
	{ value: 'gte', label: '>= (greater or equal)' },
	{ value: 'lt', label: '< (less than)' },
	{ value: 'lte', label: '<= (less or equal)' },
	{ value: 'in', label: 'in (one of)' },
];

const ORDER_STATUSES = [
	{ value: 'wc-pending', label: 'Pending payment' },
	{ value: 'wc-processing', label: 'Processing' },
	{ value: 'wc-on-hold', label: 'On hold' },
	{ value: 'wc-completed', label: 'Completed' },
	{ value: 'wc-cancelled', label: 'Cancelled' },
	{ value: 'wc-refunded', label: 'Refunded' },
	{ value: 'wc-failed', label: 'Failed' },
];

const cardStyle: React.CSSProperties = {
	background: '#fff',
	borderRadius: '8px',
	padding: '24px',
	boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
	marginBottom: '24px',
};

const sectionTitle: React.CSSProperties = {
	margin: '0 0 16px',
	fontSize: '16px',
	fontWeight: 600,
	color: '#111827',
};

function emptyRule(): Rule {
	return {
		id: 0,
		name: '',
		trigger: 'order_created',
		trigger_config: {},
		conditions: [],
		actions: [],
		enabled: false,
		priority: 0,
		created_at: '',
		updated_at: '',
	};
}

/**
 * Automation page component.
 */
export function AutomationPage() {
	const [ rules, setRules ] = useState< Rule[] | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ editing, setEditing ] = useState< Rule | null >( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ dryRunResult, setDryRunResult ] = useState< unknown >( null );
	const [ dryRunFor, setDryRunFor ] = useState< number | null >( null );
	const { addToast } = useToastContext();

	const loadRules = useCallback( () => {
		setIsLoading( true );
		apiFetch< Rule[] >( { path: 'commerceflow/v1/automation' } )
			.then( ( result ) => {
				setRules( result );
				setIsLoading( false );
			} )
			.catch( ( err: Error & { message?: string } ) => {
				setError(
					err?.message ??
						__( 'Failed to load automation rules.', 'commerceflow' )
				);
				setIsLoading( false );
			} );
	}, [] );

	useEffect( () => {
		loadRules();
	}, [ loadRules ] );

	const handleSave = useCallback( () => {
		if ( ! editing ) {
			return;
		}

		setIsSaving( true );
		const isNew = editing.id === 0;
		const path = isNew
			? 'commerceflow/v1/automation'
			: `commerceflow/v1/automation/${ editing.id }`;
		const method = isNew ? 'POST' : 'PUT';

		apiFetch< Rule >( { path, method, data: editing } )
			.then( () => {
				setEditing( null );
				addToast(
					__( 'Rule saved successfully.', 'commerceflow' ),
					'success'
				);
				setIsSaving( false );
				loadRules();
			} )
			.catch( ( err: Error & { message?: string } ) => {
				addToast(
					err?.message ??
						__( 'Failed to save rule.', 'commerceflow' ),
					'error'
				);
				setIsSaving( false );
			} );
	}, [ editing, addToast, loadRules ] );

	const handleDelete = useCallback(
		( id: number ) => {
			apiFetch< { deleted: boolean } >( {
				path: `commerceflow/v1/automation/${ id }`,
				method: 'DELETE',
			} )
				.then( () => {
					addToast(
						__( 'Rule deleted.', 'commerceflow' ),
						'success'
					);
					loadRules();
				} )
				.catch( ( err: Error & { message?: string } ) => {
					addToast(
						err?.message ??
							__( 'Failed to delete rule.', 'commerceflow' ),
						'error'
					);
				} );
		},
		[ addToast, loadRules ]
	);

	const handleToggle = useCallback(
		( rule: Rule ) => {
			const updated = { ...rule, enabled: ! rule.enabled };
			apiFetch< Rule >( {
				path: `commerceflow/v1/automation/${ rule.id }`,
				method: 'PUT',
				data: updated,
			} )
				.then( () => {
					loadRules();
				} )
				.catch( ( err: Error & { message?: string } ) => {
					addToast(
						err?.message ??
							__( 'Failed to toggle rule.', 'commerceflow' ),
						'error'
					);
				} );
		},
		[ addToast, loadRules ]
	);

	const handleDryRun = useCallback( ( id: number ) => {
		setDryRunFor( id );
		apiFetch< unknown >( {
			path: `commerceflow/v1/automation/${ id }/dry-run`,
			method: 'POST',
			data: { order_id: 0, sample: { email: 'anisur2805@gmail.com' } },
		} )
			.then( ( result ) => {
				setDryRunResult( result );
			} )
			.catch( ( err: Error & { message?: string } ) => {
				setDryRunResult( {
					error:
						err?.message ?? __( 'Dry-run failed.', 'commerceflow' ),
				} );
			} );
	}, [] );

	if ( isLoading ) {
		return <Spinner />;
	}

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
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
					marginBottom: '24px',
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
					{ __( 'Automation Rules', 'commerceflow' ) }
				</h1>
				<Button
					variant="primary"
					onClick={ () => setEditing( emptyRule() ) }
				>
					{ __( 'Add Rule', 'commerceflow' ) }
				</Button>
			</div>

			{ ( rules?.length ?? 0 ) === 0 ? (
				<div style={ cardStyle }>
					<p style={ { color: '#6b7280', margin: 0 } }>
						{ __(
							'No automation rules yet. Create your first rule to automate order lifecycle actions.',
							'commerceflow'
						) }
					</p>
				</div>
			) : (
				rules?.map( ( rule ) => (
					<RuleRow
						key={ rule.id }
						rule={ rule }
						onEdit={ () => setEditing( { ...rule } ) }
						onToggle={ () => handleToggle( rule ) }
						onDelete={ () => handleDelete( rule.id ) }
						onDryRun={ () => handleDryRun( rule.id ) }
					/>
				) )
			) }

			{ editing && (
				<RuleEditor
					rule={ editing }
					onChange={ setEditing }
					onSave={ handleSave }
					onCancel={ () => setEditing( null ) }
					isSaving={ isSaving }
				/>
			) }

			{ dryRunFor !== null && (
				<Modal
					title={ __( 'Dry-Run Result', 'commerceflow' ) }
					onRequestClose={ () => {
						setDryRunFor( null );
						setDryRunResult( null );
					} }
				>
					<pre
						style={ {
							background: '#f3f4f6',
							padding: '16px',
							borderRadius: '6px',
							fontSize: '13px',
							overflow: 'auto',
							maxWidth: '500px',
						} }
					>
						{ dryRunResult
							? JSON.stringify( dryRunResult, null, 2 )
							: __( 'Running…', 'commerceflow' ) }
					</pre>
				</Modal>
			) }
		</div>
	);
}

/**
 * Rule row component.
 * @param root0
 * @param root0.rule
 * @param root0.onEdit
 * @param root0.onToggle
 * @param root0.onDelete
 * @param root0.onDryRun
 */
function RuleRow( {
	rule,
	onEdit,
	onToggle,
	onDelete,
	onDryRun,
}: {
	rule: Rule;
	onEdit: () => void;
	onToggle: () => void;
	onDelete: () => void;
	onDryRun: () => void;
} ) {
	return (
		<div style={ cardStyle }>
			<div
				style={ {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
				} }
			>
				<div>
					<strong style={ { fontSize: '15px', color: '#111827' } }>
						{ rule.name }
					</strong>
					<span
						style={ {
							marginLeft: '12px',
							fontSize: '12px',
							color: '#6b7280',
							textTransform: 'uppercase',
							letterSpacing: '0.5px',
						} }
					>
						{ rule.trigger.replace( /_/g, ' ' ) }
					</span>
					{ rule.enabled && (
						<span
							style={ {
								marginLeft: '12px',
								fontSize: '11px',
								color: '#16a34a',
								background: '#dcfce7',
								padding: '2px 8px',
								borderRadius: '10px',
							} }
						>
							{ __( 'Active', 'commerceflow' ) }
						</span>
					) }
				</div>
				<div style={ { display: 'flex', gap: '8px' } }>
					<Button variant="secondary" isSmall onClick={ onDryRun }>
						{ __( 'Test', 'commerceflow' ) }
					</Button>
					<Button variant="secondary" isSmall onClick={ onToggle }>
						{ rule.enabled
							? __( 'Disable', 'commerceflow' )
							: __( 'Enable', 'commerceflow' ) }
					</Button>
					<Button variant="secondary" isSmall onClick={ onEdit }>
						{ __( 'Edit', 'commerceflow' ) }
					</Button>
					<Button
						variant="secondary"
						isSmall
						isDestructive
						onClick={ onDelete }
					>
						{ __( 'Delete', 'commerceflow' ) }
					</Button>
				</div>
			</div>
			<div
				style={ {
					marginTop: '12px',
					fontSize: '13px',
					color: '#6b7280',
				} }
			>
				{ rule.actions.length } { __( 'action(s)' ) } ·{ ' ' }
				{ __( 'priority' ) }: { rule.priority }
			</div>
		</div>
	);
}

/**
 * Rule editor modal.
 * @param root0
 * @param root0.rule
 * @param root0.onChange
 * @param root0.onSave
 * @param root0.onCancel
 * @param root0.isSaving
 */
function RuleEditor( {
	rule,
	onChange,
	onSave,
	onCancel,
	isSaving,
}: {
	rule: Rule;
	onChange: ( rule: Rule ) => void;
	onSave: () => void;
	onCancel: () => void;
	isSaving: boolean;
} ) {
	const update = ( patch: Partial< Rule > ) =>
		onChange( { ...rule, ...patch } );

	return (
		<Modal
			title={
				rule.id
					? __( 'Edit Rule', 'commerceflow' )
					: __( 'New Rule', 'commerceflow' )
			}
			onRequestClose={ onCancel }
			shouldCloseOnClickOutside={ false }
		>
			<div style={ { minWidth: '520px', maxWidth: '600px' } }>
				<TextControl
					label={ __( 'Rule Name', 'commerceflow' ) }
					value={ rule.name }
					onChange={ ( v ) => update( { name: v } ) }
				/>

				<SelectControl
					label={ __( 'Trigger', 'commerceflow' ) }
					value={ rule.trigger }
					options={ TRIGGERS }
					onChange={ ( v ) => update( { trigger: v } ) }
				/>

				{ rule.trigger === 'order_status_changed' && (
					<div
						style={ {
							display: 'grid',
							gridTemplateColumns: '1fr 1fr',
							gap: '12px',
						} }
					>
						<SelectControl
							label={ __( 'From Status', 'commerceflow' ) }
							value={ rule.trigger_config.from ?? '' }
							options={ [
								{
									value: '',
									label: __( 'Any', 'commerceflow' ),
								},
								...ORDER_STATUSES,
							] }
							onChange={ ( v ) =>
								update( {
									trigger_config: {
										...rule.trigger_config,
										from: v,
									},
								} )
							}
						/>
						<SelectControl
							label={ __( 'To Status', 'commerceflow' ) }
							value={ rule.trigger_config.to ?? '' }
							options={ [
								{
									value: '',
									label: __( 'Any', 'commerceflow' ),
								},
								...ORDER_STATUSES,
							] }
							onChange={ ( v ) =>
								update( {
									trigger_config: {
										...rule.trigger_config,
										to: v,
									},
								} )
							}
						/>
					</div>
				) }

				<h3 style={ sectionTitle }>
					{ __( 'Conditions', 'commerceflow' ) }
				</h3>
				{ rule.conditions.map( ( cond, idx ) => (
					<div
						key={ idx }
						style={ {
							display: 'grid',
							gridTemplateColumns: '1fr 1fr 1fr auto',
							gap: '8px',
							marginBottom: '8px',
						} }
					>
						<TextControl
							placeholder={ __(
								'field (e.g. total)',
								'commerceflow'
							) }
							value={ cond.field }
							onChange={ ( v ) =>
								updateCondition(
									rule,
									idx,
									{ field: v },
									onChange
								)
							}
						/>
						<SelectControl
							value={ cond.operator }
							options={ OPERATORS }
							onChange={ ( v ) =>
								updateCondition(
									rule,
									idx,
									{ operator: v },
									onChange
								)
							}
						/>
						<TextControl
							placeholder={ __( 'value', 'commerceflow' ) }
							value={ String( cond.value ) }
							onChange={ ( v ) =>
								updateCondition(
									rule,
									idx,
									{ value: v },
									onChange
								)
							}
						/>
						<Button
							variant="secondary"
							isSmall
							isDestructive
							onClick={ () =>
								onChange( {
									...rule,
									conditions: rule.conditions.filter(
										( _, i ) => i !== idx
									),
								} )
							}
						>
							×
						</Button>
					</div>
				) ) }
				<Button
					variant="tertiary"
					isSmall
					onClick={ () =>
						onChange( {
							...rule,
							conditions: [
								...rule.conditions,
								{ field: '', operator: 'eq', value: '' },
							],
						} )
					}
				>
					{ __( '+ Add Condition', 'commerceflow' ) }
				</Button>

				<h3 style={ { ...sectionTitle, marginTop: '24px' } }>
					{ __( 'Actions', 'commerceflow' ) }
				</h3>
				{ rule.actions.map( ( action, idx ) => (
					<ActionEditor
						key={ idx }
						action={ action }
						onChange={ ( a ) =>
							updateAction( rule, idx, a, onChange )
						}
						onRemove={ () =>
							onChange( {
								...rule,
								actions: rule.actions.filter(
									( _, i ) => i !== idx
								),
							} )
						}
					/>
				) ) }
				<Button
					variant="tertiary"
					isSmall
					onClick={ () =>
						onChange( {
							...rule,
							actions: [
								...rule.actions,
								{ type: 'change_status', config: {} },
							],
						} )
					}
				>
					{ __( '+ Add Action', 'commerceflow' ) }
				</Button>

				<div style={ { marginTop: '24px' } }>
					<TextControl
						label={ __( 'Priority', 'commerceflow' ) }
						type="number"
						value={ String( rule.priority ) }
						onChange={ ( v ) =>
							update( { priority: parseInt( v, 10 ) || 0 } )
						}
					/>
					<ToggleControl
						label={ __( 'Enabled', 'commerceflow' ) }
						checked={ rule.enabled }
						onChange={ ( v ) => update( { enabled: v } ) }
					/>
				</div>

				<div
					style={ {
						display: 'flex',
						justifyContent: 'flex-end',
						gap: '8px',
						marginTop: '24px',
					} }
				>
					<Button variant="tertiary" onClick={ onCancel }>
						{ __( 'Cancel', 'commerceflow' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ onSave }
						isBusy={ isSaving }
						disabled={ isSaving || ! rule.name }
					>
						{ isSaving
							? __( 'Saving…', 'commerceflow' )
							: __( 'Save Rule', 'commerceflow' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
}

function updateCondition(
	rule: Rule,
	idx: number,
	patch: Partial< Condition >,
	onChange: ( rule: Rule ) => void
) {
	onChange( {
		...rule,
		conditions: rule.conditions.map( ( c, i ) =>
			i === idx ? { ...c, ...patch } : c
		),
	} );
}

function updateAction(
	rule: Rule,
	idx: number,
	action: Action,
	onChange: ( rule: Rule ) => void
) {
	onChange( {
		...rule,
		actions: rule.actions.map( ( a, i ) => ( i === idx ? action : a ) ),
	} );
}

/**
 * Action editor — renders config fields per action type.
 * @param root0
 * @param root0.action
 * @param root0.onChange
 * @param root0.onRemove
 */
function ActionEditor( {
	action,
	onChange,
	onRemove,
}: {
	action: Action;
	onChange: ( action: Action ) => void;
	onRemove: () => void;
} ) {
	return (
		<div
			style={ {
				background: '#f9fafb',
				padding: '12px',
				borderRadius: '6px',
				marginBottom: '8px',
			} }
		>
			<div
				style={ {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
					marginBottom: '8px',
				} }
			>
				<SelectControl
					value={ action.type }
					options={ ACTION_TYPES }
					onChange={ ( v ) => onChange( { ...action, type: v } ) }
				/>
				<Button
					variant="secondary"
					isSmall
					isDestructive
					onClick={ onRemove }
				>
					×
				</Button>
			</div>

			{ action.type === 'change_status' && (
				<SelectControl
					label={ __( 'New Status', 'commerceflow' ) }
					value={ ( action.config.status as string ) ?? '' }
					options={ ORDER_STATUSES }
					onChange={ ( v ) =>
						onChange( { ...action, config: { status: v } } )
					}
				/>
			) }

			{ action.type === 'add_order_note' && (
				<TextareaControl
					label={ __( 'Note', 'commerceflow' ) }
					value={ ( action.config.note as string ) ?? '' }
					onChange={ ( v ) =>
						onChange( { ...action, config: { note: v } } )
					}
				/>
			) }

			{ action.type === 'generate_coupon' && (
				<div
					style={ {
						display: 'grid',
						gridTemplateColumns: '1fr 1fr 1fr',
						gap: '8px',
					} }
				>
					<TextControl
						label={ __( 'Coupon Code', 'commerceflow' ) }
						value={ ( action.config.code as string ) ?? '' }
						onChange={ ( v ) =>
							onChange( {
								...action,
								config: { ...action.config, code: v },
							} )
						}
					/>
					<TextControl
						label={ __( 'Amount', 'commerceflow' ) }
						type="number"
						value={ String( action.config.amount ?? '' ) }
						onChange={ ( v ) =>
							onChange( {
								...action,
								config: {
									...action.config,
									amount: parseFloat( v ) || 0,
								},
							} )
						}
					/>
					<SelectControl
						label={ __( 'Type', 'commerceflow' ) }
						value={ ( action.config.type as string ) ?? 'percent' }
						options={ [
							{ value: 'percent', label: 'Percentage' },
							{ value: 'fixed_cart', label: 'Fixed cart' },
						] }
						onChange={ ( v ) =>
							onChange( {
								...action,
								config: { ...action.config, type: v },
							} )
						}
					/>
				</div>
			) }

			{ action.type === 'call_webhook' && (
				<TextControl
					label={ __( 'Webhook URL', 'commerceflow' ) }
					type="url"
					value={ ( action.config.url as string ) ?? '' }
					onChange={ ( v ) =>
						onChange( { ...action, config: { url: v } } )
					}
				/>
			) }
		</div>
	);
}
