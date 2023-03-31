window.bulmaOptions = {
    autoParseDocument: false
}

window.bulmaInitialized = false;

import {Bulma} from 'cover-style-system/src/js';
import './general';
import './forms';
import './pages';

document.addEventListener('DOMContentLoaded', () => {
    Bulma.traverseDOM();
    window.bulmaInitialized = true;
});
