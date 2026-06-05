import { apiClient } from './apiClient';
import type { components } from './schema';

export interface Car {
  id: string;
  brand: string;
  model: string;
  pricePerDay: number; // in cents
  available: boolean;
}

export type AddCarRequest = components['schemas']['AddCarRequest'];
export type UpdateCarRequest = components['schemas']['UpdateCarRequest'];

export const carApi = {
  list: async (): Promise<Car[]> => {
    const response = await apiClient.get<Car[]>('/cars');
    return response.data;
  },

  add: async (data: AddCarRequest): Promise<void> => {
    await apiClient.post('/cars', data);
  },

  update: async (id: string, data: UpdateCarRequest): Promise<void> => {
    await apiClient.put(`/cars/${id}`, data);
  },

  remove: async (id: string): Promise<void> => {
    await apiClient.delete(`/cars/${id}`);
  },
};
