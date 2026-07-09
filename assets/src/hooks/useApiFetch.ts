/**
 * React hook wrapping @wordpress/api-fetch for typed REST calls.
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Set the REST nonce on page load.
 */
apiFetch.use(
	apiFetch.createNonceMiddleware(
		( window as any ).COMMERCEFLOW_SETTINGS?.nonce ?? ''
	)
);

/**
 * Hook: fetch data from a REST endpoint with loading/error state.
 *
 * @template T
 * @param    path REST path relative to the REST root.
 * @return   Hook state and refetch function.
 */
export function useApiFetch< T >( path: string ) {
	const [ data, setData ] = useState< T | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );

	const fetchData = useCallback( () => {
		setIsLoading( true );
		setError( null );

		apiFetch< T >( { path } )
			.then( ( result ) => {
				setData( result );
				setIsLoading( false );
			} )
			.catch( ( err: Error & { message?: string } ) => {
				setError( err?.message ?? 'Unknown error' );
				setIsLoading( false );
			} );
	}, [ path ] );

	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	return { data, isLoading, error, refetch: fetchData };
}
