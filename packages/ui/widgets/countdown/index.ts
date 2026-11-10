/**
 * Script of the countdown widget asset (BLD-12): loaded only on pages that show it.
 */
import { countdown } from '../countdown/countdown.behavior';

window.UltraMaxUI?.define( 'countdown', countdown );
