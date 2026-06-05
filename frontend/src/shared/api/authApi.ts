import { apiClient } from './apiClient';
import type { paths } from './schema';

type LoginRequest = paths['/api/login']['post']['requestBody']['content']['application/json'];
type LoginResponse = paths['/api/login']['post']['responses']['200']['content']['application/json'];
type RegisterRequest = paths['/api/register']['post']['requestBody']['content']['application/json'];

export const authApi = {
  login: async (data: LoginRequest): Promise<LoginResponse> => {
    const response = await apiClient.post<LoginResponse>('/login', data);
    return response.data;
  },

  register: async (data: RegisterRequest): Promise<void> => {
    await apiClient.post('/register', data);
  },
};
