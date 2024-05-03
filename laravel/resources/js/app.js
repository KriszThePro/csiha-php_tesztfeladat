import './bootstrap';
import router from './router';
import { createApp } from 'vue';
import vuetify from './vuetify';

import DefaultLayout from './layouts/DefaultLayout.vue';

createApp(DefaultLayout).use(router).use(vuetify).mount('#app');
