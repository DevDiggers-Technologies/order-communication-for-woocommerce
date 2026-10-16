'use strict';

import './front.less';

const settings = window.ddwcocChatObj || {};

const post = ( data ) =>
	fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data } ).then( ( response ) => response.json() );

const initChat = ( chat ) => {
	const list = chat.querySelector( '.ddwcoc-chat-messages' );
	const empty = chat.querySelector( '.ddwcoc-chat-empty' );
	const form = chat.querySelector( '.ddwcoc-chat-form' );
	const notice = chat.querySelector( '.ddwcoc-chat-notice' );
	const input = chat.querySelector( '.ddwcoc-chat-input' );
	const sendButton = chat.querySelector( '.ddwcoc-chat-send' );
	const sendLabel = sendButton.querySelector( '.ddwcoc-chat-send-label' );
	const toggleButton = chat.querySelector( '.ddwcoc-toggle-conversation' );
	const stateLabel = chat.querySelector( '.ddwcoc-chat-state' );

	let lastId = Number( chat.dataset.lastId ) || 0;
	let busy = false;
	let errorTimer;

	const request = ( action ) => {
		const data = new FormData();
		data.append( 'action', action );
		data.append( 'nonce', settings.nonce );
		data.append( 'order_id', chat.dataset.orderId );
		data.append( 'context', chat.dataset.context );
		data.append( 'after_id', lastId );
		return data;
	};

	const scrollToBottom = () => {
		list.scrollTop = list.scrollHeight;
	};

	const updateReceipts = ( seenUntil ) => {
		list.querySelectorAll( '.ddwcoc-message-own' ).forEach( ( message ) => {
			message.classList.toggle( 'is-seen', Number( message.dataset.time ) <= seenUntil );
		} );
	};

	const showError = ( message ) => {
		notice.textContent = message || settings.i18n.error;
		notice.hidden = false;
		notice.classList.add( 'is-error' );
		clearTimeout( errorTimer );
		errorTimer = setTimeout( () => {
			notice.hidden = true;
			notice.classList.remove( 'is-error' );
		}, 6000 );
	};

	const apply = ( data ) => {
		if ( data.html ) {
			const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 120;
			const holder = document.createElement( 'div' );
			holder.innerHTML = data.html;

			[ ...holder.children ].forEach( ( message ) => {
				if ( ! list.querySelector( `[data-id="${ message.dataset.id }"]` ) ) {
					list.insertBefore( message, empty );
				}
			} );

			empty.hidden = true;

			if ( nearBottom ) {
				scrollToBottom();
			}
		}

		lastId = Math.max( lastId, data.lastId );
		updateReceipts( data.seenUntil );

		chat.classList.toggle( 'is-closed', data.closed );
		stateLabel.textContent = data.closed ? settings.i18n.closed : settings.i18n.open;

		if ( toggleButton ) {
			toggleButton.dataset.closed = data.closed ? '0' : '1';
			toggleButton.textContent = data.closed ? settings.i18n.reopen : settings.i18n.close;
		}

		if ( ! notice.classList.contains( 'is-error' ) ) {
			notice.textContent = data.blocked;
			notice.hidden = ! data.blocked;
		}
		form.hidden = !! data.blocked;
	};

	const send = () => {
		if ( busy || ! input.value.trim() ) {
			return;
		}

		const data = request( 'ddwcoc_send_message' );
		data.append( 'message', input.value );

		busy = true;
		sendButton.disabled = true;
		sendLabel.textContent = settings.i18n.sending;

		post( data )
			.then( ( response ) => {
				if ( ! response.success ) {
					showError( response.data && response.data.message );
					return;
				}

				input.value = '';
				input.style.height = '';

				apply( response.data );
				scrollToBottom();
			} )
			.catch( () => showError() )
			.finally( () => {
				busy = false;
				sendButton.disabled = false;
				sendLabel.textContent = settings.i18n.send;
			} );
	};

	const poll = () => {
		if ( busy || document.hidden ) {
			return;
		}

		post( request( 'ddwcoc_fetch_messages' ) ).then( ( response ) => response.success && apply( response.data ) ).catch( () => {} );
	};

	// Composer.
	sendButton.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		send();
	} );

	if ( 'FORM' === form.tagName ) {
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			send();
		} );
	}

	input.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' === event.key && ! event.shiftKey && ! event.isComposing ) {
			event.preventDefault();
			send();
		}
	} );

	input.addEventListener( 'input', () => {
		input.style.height = '';
		input.style.height = `${ Math.min( input.scrollHeight, 220 ) }px`;
	} );

	if ( toggleButton ) {
		toggleButton.addEventListener( 'click', () => {
			const closing = '1' === toggleButton.dataset.closed;

			// eslint-disable-next-line no-alert
			if ( closing && ! window.confirm( settings.i18n.confirmClose ) ) {
				return;
			}

			const data = request( 'ddwcoc_set_conversation_status' );
			data.append( 'closed', closing ? '1' : '' );
			toggleButton.disabled = true;

			post( data )
				.then( ( response ) => ( response.success ? apply( response.data ) : showError( response.data && response.data.message ) ) )
				.finally( () => {
					toggleButton.disabled = false;
				} );
		} );
	}

	updateReceipts( Number( chat.dataset.seenUntil ) );

	// Open on the newest message without animating, and keep it there while images load.
	const jumpToBottom = () => {
		list.style.scrollBehavior = 'auto';
		scrollToBottom();
		list.style.scrollBehavior = '';
	};

	jumpToBottom();
	list.querySelectorAll( 'img' ).forEach( ( img ) => ! img.complete && img.addEventListener( 'load', jumpToBottom, { once: true } ) );

	if ( settings.pollInterval ) {
		setInterval( poll, settings.pollInterval );
		document.addEventListener( 'visibilitychange', poll );
	}

	if ( '#ddwcoc-chat' === window.location.hash ) {
		chat.scrollIntoView( { behavior: 'smooth' } );
	}
};

document.addEventListener( 'DOMContentLoaded', () => {
	document.querySelectorAll( '.ddwcoc-chat' ).forEach( initChat );
} );
