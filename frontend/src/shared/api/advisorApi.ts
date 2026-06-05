import { apiClient } from './apiClient';
import type { components } from './schema';

export type SubmitMessageRequest = components['schemas']['SubmitMessageRequest'];

export const advisorApi = {
  submitMessage: async (sessionId: string, data: SubmitMessageRequest): Promise<void> => {
    await apiClient.post(`/cars/advisor/chat/${sessionId}`, data);
  },

  getSseUrl: (sessionId: string): string => {
    const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api';
    // If it's a relative URL, we resolve it relative to the current host
    if (apiBaseUrl.startsWith('http')) {
      return `${apiBaseUrl}/cars/advisor/sse/${sessionId}`;
    }
    return `${window.location.origin}${apiBaseUrl}/cars/advisor/sse/${sessionId}`;
  },
};
