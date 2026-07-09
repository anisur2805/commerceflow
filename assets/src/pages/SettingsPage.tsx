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
import { ToggleControl, Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

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
				setIsSaving( false );
			} )
			.catch( ( err: Error & { message?: string } ) => {
				setNotice( {
					type: 'error',
					message:
						err?.message ??
						__( 'Failed to save settings.', 'commerceflow' ),
				} );
				setIsSaving( false );
			} );
	}, [ settings ] );

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
