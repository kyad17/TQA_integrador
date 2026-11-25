import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import CentroTqa from './CentroTqa';
import './app.css';

createInertiaApp({
    resolve: () => CentroTqa,
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
