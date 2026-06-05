import { apiClient } from './apiClient';
import type { components } from './schema';

export type CreateSubscriptionRequest = components['schemas']['CreateSubscriptionRequest'];

export interface CreateSubscriptionResponse {
  subscriptionId: string;
  paymentUrl?: string; // Optional if immediate payment checkout URL is returned
}

export const subscriptionApi = {
  create: async (data: CreateSubscriptionRequest): Promise<CreateSubscriptionResponse> => {
    const response = await apiClient.post<CreateSubscriptionResponse>('/subscriptions', data);
    return response.data;
  },
};
