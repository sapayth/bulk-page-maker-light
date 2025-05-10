/**
 * Bulk Page Maker React Application
 */
import {createRoot} from '@wordpress/element';
import App from './App';
import './index.css';

// Wait for DOM to be ready
document.addEventListener( 'DOMContentLoaded', () => {
    const container = createRoot( document.getElementById( 'bpm-create-new' ) );

    // Only render if the container exists
    if (container) {
        container.render( <App/> );
    }
} );