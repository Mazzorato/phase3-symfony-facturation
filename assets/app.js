import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

document.addEventListener('chartjs:pre-connect', function(event) {
    event.detail.config.options.scales.y.ticks = {
        callback: function(value) {
            return value + '€';
        }
    };
});

import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
