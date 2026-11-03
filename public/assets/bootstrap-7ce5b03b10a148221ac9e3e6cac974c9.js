import { startStimulusApp } from '@symfony/stimulus-bundle';
import TableController from './controllers/table_controller.js';
import ModalController from './controllers/modal_controller.js';
import NotificationController from './controllers/notification_controller.js';
import DropdownController from './controllers/dropdown_controller.js';
import SidebarMenuController from './controllers/sidebar_menu_controller.js';

export const app = startStimulusApp();

// Enregistrement explicite et synchrone des contrôleurs principaux
app.register('table', TableController);
app.register('modal', ModalController);
app.register('notification', NotificationController);
app.register('dropdown', DropdownController);
app.register('sidebar-menu', SidebarMenuController);
