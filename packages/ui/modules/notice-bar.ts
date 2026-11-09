/**
 * Script of the switchable notice-bar module: loaded only when the module is on.
 */
import { noticeBar } from '../components/notice-bar/notice-bar.behavior';

window.UltraMaxUI?.define( 'notice-bar', noticeBar );
