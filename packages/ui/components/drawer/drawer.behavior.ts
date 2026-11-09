/**
 * Drawer behaviour: the same native modal dialog as Dialog.
 */
import { dialog, type DialogController } from '../dialog/dialog.behavior';

/**
 * Mounts the behaviour.
 *
 * @param element The `dialog` element.
 */
export function drawer( element: HTMLElement ): DialogController {
	return dialog( element );
}