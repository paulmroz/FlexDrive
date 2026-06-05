import { create } from 'zustand';
import { decodeJwt } from '../../../shared/lib/jwt';

interface UserState {
  token: string | null;
  email: string | null;
  roles: string[];
  isAuthenticated: boolean;
  isAdmin: boolean;
  initialize: () => void;
  setAuth: (token: string) => void;
  clearAuth: () => void;
}

export const useUserStore = create<UserState>((set) => ({
  token: null,
  email: null,
  roles: [],
  isAuthenticated: false,
  isAdmin: false,

  initialize: () => {
    const token = localStorage.getItem('token');
    if (!token) return;

    const payload = decodeJwt(token);
    if (!payload || payload.exp * 1000 < Date.now()) {
      localStorage.removeItem('token');
      return;
    }

    set({
      token,
      email: payload.email,
      roles: payload.roles,
      isAuthenticated: true,
      isAdmin: payload.roles.includes('ROLE_ADMIN'),
    });
  },

  setAuth: (token: string) => {
    localStorage.setItem('token', token);
    const payload = decodeJwt(token);

    if (payload) {
      set({
        token,
        email: payload.email,
        roles: payload.roles,
        isAuthenticated: true,
        isAdmin: payload.roles.includes('ROLE_ADMIN'),
      });
    }
  },

  clearAuth: () => {
    localStorage.removeItem('token');
    set({
      token: null,
      email: null,
      roles: [],
      isAuthenticated: false,
      isAdmin: false,
    });
  },
}));
