/**
 * Settings page — view and update plugin settings via REST.
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	Button,
	Spinner,
	Notice,
	TextControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useToastContext } from '../components/AdminPage';

interface Settings {
	enable_dashboard_cache: boolean;
	dashboard_cache_ttl: number;
}

type NoticeType = 'success' | 'error';

/**
 * Settings page component.
 */
export function SettingsPage() {
	const [ settings, setSettings ] = useState< Settings | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState< {
		type: NoticeType;
		message: string;
	} | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const { addToast } = useToastContext();

	useEffect( () => {
		apiFetch< Settings >( { path: 'commerceflow/v1/settings' } )
			.then( ( result ) => {
				setSettings( result );
				setIsLoading( false );
			} )
			.catch( ( err: Error & { message?: string } ) => {
				setError(
					err?.message ??
						__( 'Failed to load settings.', 'commerceflow' )
				);
				setIsLoading( false );
			} );
	}, [] );

	const handleToggle = useCallback( ( value: boolean ) => {
		setSettings( ( prev ) =>
			prev ? { ...prev, enable_dashboard_cache: value } : prev
		);
	}, [] );

	const handleTtlChange = useCallback( ( value: string ) => {
		const num = parseInt( value, 10 );
		if ( isNaN( num ) || num < 30 ) {
			return;
		}
		setSettings( ( prev ) =>
			prev ? { ...prev, dashboard_cache_ttl: num } : prev
		);
	}, [] );

	const handleSave = useCallback( () => {
		if ( ! settings ) {
			return;
		}

		setIsSaving( true );
		setNotice( null );

		apiFetch< Settings >( {
			path: 'commerceflow/v1/settings',
			method: 'PUT',
			data: settings,
		} )
			.then( ( result ) => {
				setSettings( result );
				setNotice( {
					type: 'success',
					message: __( 'Settings saved.', 'commerceflow' ),
				} );
				addToast(
					__( 'Settings saved successfully.', 'commerceflow' ),
					'success'
				);
				setIsSaving( false );
			} )
			.catch( ( err: Error & { message?: string } ) => {
				setNotice( {
					type: 'error',
					message:
						err?.message ??
						__( 'Failed to save settings.', 'commerceflow' ),
				} );
				addToast(
					err?.message ??
						__( 'Failed to save settings.', 'commerceflow' ),
					'error'
				);
				setIsSaving( false );
			} );
	}, [ settings, addToast ] );

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

	if ( ! settings ) {
		return null;
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
				{ __( 'Settings', 'commerceflow' ) }
			</h1>

			{ notice && (
				<div style={ { marginBottom: '16px' } }>
					<Notice status={ notice.type } isDismissible={ false }>
						{ notice.message }
					</Notice>
				</div>
			) }

			<div
				style={ {
					background: '#fff',
					borderRadius: '8px',
					padding: '24px',
					boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
					maxWidth: '500px',
				} }
			>
				<ToggleControl
					label={ __( 'Enable dashboard cache', 'commerceflow' ) }
					help={ __(
						'Cache dashboard metrics to improve load time.',
						'commerceflow'
					) }
					checked={ settings.enable_dashboard_cache }
					onChange={ handleToggle }
				/>

				<TextControl
					label={ __( 'Cache TTL (seconds)', 'commerceflow' ) }
					help={ __(
						'Time in seconds before the dashboard cache expires (30–3600).',
						'commerceflow'
					) }
					type="number"
					min={ 30 }
					max={ 3600 }
					value={ String( settings.dashboard_cache_ttl ) }
					onChange={ handleTtlChange }
				/>

				<div style={ { marginTop: '24px' } }>
					<Button
						variant="primary"
						onClick={ handleSave }
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ isSaving
							? __( 'Saving…', 'commerceflow' )
							: __( 'Save Settings', 'commerceflow' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}
