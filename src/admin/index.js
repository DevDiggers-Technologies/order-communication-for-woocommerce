'use strict';

import './admin.less';

const settings = window.ddwcocAdminObj || {};

// Unread conversations on the Inbox header tab.
const initInboxCount = () => {
	const count = parseInt( settings.awaiting, 10 );

	if ( ! count ) {
		return;
	}

	document.querySelectorAll( '.ddfw-header-tab-wrapper a[href*="menu=inbox"]' ).forEach( ( link ) => {
		const badge = document.createElement( 'span' );
		badge.className = 'ddwcoc-menu-count';
		badge.textContent = count > 99 ? '99+' : count;
		link.append( ' ', badge );
	} );
};

document.addEventListener( 'DOMContentLoaded', initInboxCount );
