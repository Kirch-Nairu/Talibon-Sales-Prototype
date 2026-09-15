import { createContext, useContext } from 'react';
import type { LiveNotification } from '../../types';

export const NotificationContext = createContext<LiveNotification[]>([]);
export const usePortalNotifications = () => useContext(NotificationContext);
