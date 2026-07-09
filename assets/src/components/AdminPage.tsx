/**
 * Admin layout shell — sidebar nav + content area.
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import { useNavigate, useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import type { ReactNode, FC } from 'react';

interface NavItem {
	path: string;
	label: string;
}

const navItems: NavItem[] = [
	{ path: '/dashboard', label: __( 'Dashboard', 'commerceflow' ) },
	{ path: '/settings', label: __( 'Settings', 'commerceflow' ) },
];

interface AdminPageProps {
	children: ReactNode;
}

export const AdminPage: FC< AdminPageProps > = ( { children } ) => {
	const navigate = useNavigate();
	const location = useLocation();
	const [ collapsed, setCollapsed ] = useState( false );

	const toggleCollapse = useCallback( () => {
		setCollapsed( ( v ) => ! v );
	}, [] );

	const navWidth = collapsed ? '60px' : '220px';

	return (
		<div
			style={ {
				display: 'flex',
				minHeight: '100vh',
				fontFamily:
					'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			} }
		>
			<nav
				style={ {
					width: navWidth,
					minWidth: navWidth,
					background: '#1e1e2e',
					color: '#cdd6f4',
					display: 'flex',
					flexDirection: 'column',
					transition: 'width 0.2s ease',
					overflow: 'hidden',
				} }
				role="navigation"
				aria-label={ __( 'CommerceFlow navigation', 'commerceflow' ) }
			>
				<div
					style={ {
						padding: '16px',
						fontSize: collapsed ? '14px' : '18px',
						fontWeight: 700,
						borderBottom: '1px solid #313244',
						textAlign: collapsed ? 'center' : 'left',
						whiteSpace: 'nowrap',
					} }
				>
					{ collapsed ? 'CF' : 'CommerceFlow' }
				</div>

				<ul
					style={ { listStyle: 'none', margin: 0, padding: '8px 0' } }
				>
					{ navItems.map( ( item ) => (
						<li key={ item.path }>
							<button
								type="button"
								onClick={ () => navigate( item.path ) }
								style={ {
									display: 'block',
									width: '100%',
									padding: '12px 16px',
									background:
										location.pathname === item.path
											? '#313244'
											: 'transparent',
									border: 'none',
									color:
										location.pathname === item.path
											? '#89b4fa'
											: '#cdd6f4',
									cursor: 'pointer',
									fontSize: '14px',
									textAlign: 'left',
									whiteSpace: 'nowrap',
									overflow: 'hidden',
									textOverflow: 'ellipsis',
								} }
								aria-current={
									location.pathname === item.path
										? 'page'
										: undefined
								}
							>
								{ collapsed
									? item.label.charAt( 0 )
									: item.label }
							</button>
						</li>
					) ) }
				</ul>

				<div style={ { marginTop: 'auto', padding: '8px' } }>
					<button
						type="button"
						onClick={ toggleCollapse }
						style={ {
							width: '100%',
							padding: '8px',
							background: 'transparent',
							border: '1px solid #313244',
							color: '#cdd6f4',
							cursor: 'pointer',
							fontSize: '12px',
						} }
						aria-label={
							collapsed
								? __( 'Expand sidebar', 'commerceflow' )
								: __( 'Collapse sidebar', 'commerceflow' )
						}
					>
						{ collapsed ? '→' : '←' }
					</button>
				</div>
			</nav>

			<main
				style={ {
					flex: 1,
					padding: '24px',
					background: '#f5f5f5',
					overflowY: 'auto',
				} }
				role="main"
			>
				{ children }
			</main>
		</div>
	);
};
