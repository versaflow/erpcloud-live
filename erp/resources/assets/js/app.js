
/**
 * First we will load all of this project's JavaScript dependencies which
 * include Vue and Vue Resource. This gives a great starting point for
 * building robust, powerful web applications using Vue and Laravel.
 */
require('./bootstrap');
import Vue from 'vue';
import Quasar from 'quasar-framework'
import 'quasar-framework/dist/quasar.mat.css'
import 'quasar-extras/material-icons'
import 'quasar-extras/ionicons'
import 'quasar-extras/fontawesome'

Vue.use(Quasar)
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
import DrawerComponent from './components/DrawerComponent.vue';

Vue.component('drawer-component', DrawerComponent);

const app = new Vue({
    el: '#app',
});
