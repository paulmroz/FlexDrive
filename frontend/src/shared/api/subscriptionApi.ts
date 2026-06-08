import { apiClient } from './apiClient';
import type { components } from './schema';

export type CreateSubscriptionRequest = components['schemas']['CreateSubscriptionRequest'];

export interface CreateSubscriptionResponse {
  subscriptionId: string;
}

export interface SubscriptionResponse {
  id: string;
  carId: string;
  carBrand: string;
  carModel: string;
  status: 'pending_payment' | 'active' | 'cancelled' | 'expired';
  startDate: string;
  endDate: string | null;
  createdAt: string;
}

export const subscriptionApi = {
  create: async (data: CreateSubscriptionRequest): Promise<CreateSubscriptionResponse> => {
    const response = await apiClient.post<CreateSubscriptionResponse>('/subscriptions', data);
    return response.data;
  },
  checkout: async (id: string): Promise<{ paymentUrl: string }> => {
    const response = await apiClient.post<{ paymentUrl: string }>(`/subscriptions/${id}/checkout`);
    return response.data;
  },
  list: async (): Promise<SubscriptionResponse[]> => {
    const response = await apiClient.get<SubscriptionResponse[]>('/subscriptions');
    return response.data;
  },
  cancel: async (id: string): Promise<{ message: string }> => {
    const response = await apiClient.post<{ message: string }>(`/subscriptions/${id}/cancel`);
    return response.data;
  },
};
