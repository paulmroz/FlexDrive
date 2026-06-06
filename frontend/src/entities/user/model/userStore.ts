import { create } from 'zustand';
import { decodeJwt } from '../../../shared/lib/jwt';

interface UserState {
  token: string | null;
  email: string | null;
  roles: string[];
  isAuthenticated: boolean;
  isAdmin: boolean;
  setAuth: (token: string) => void;
  clearAuth: () => void;
}

const getInitialState = () => {
  const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
  if (!token) {
    return {
      token: null,
      email: null,
      roles: [],
      isAuthenticated: false,
      isAdmin: false,
    };
  }

  const payload = decodeJwt(token);
  if (!payload || payload.exp * 1000 < Date.now()) {
    if (typeof window !== 'undefined') {
      localStorage.removeItem('token');
    }
    return {
      token: null,
      email: null,
      roles: [],
      isAuthenticated: false,
      isAdmin: false,
    };
  }

  return {
    token,
    email: payload.email,
    roles: payload.roles,
    isAuthenticated: true,
    isAdmin: payload.roles.includes('ROLE_ADMIN'),
  };
};

const initialState = getInitialState();

export const useUserStore = create<UserState>((set) => ({
  ...initialState,

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

