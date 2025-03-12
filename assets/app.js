import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import '../public/toast.js';
import Chart from 'chart.js';
import 'chartjs-plugin-datalabels';
import 'chartjs-plugin-zoom';   
import 'chartjs-plugin-annotation';
import 'chartjs-plugin-streaming';
import 'chartjs-plugin-crosshair';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
