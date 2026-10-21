import { createApp } from 'vue';
import App from './App.vue';

const container = document.getElementById('app');

createApp(App, {
    appName: container?.dataset.appName ?? 'MyCar',
}).mount(container);
