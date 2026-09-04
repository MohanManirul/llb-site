import api from '@/lib/api-client';

export const createPayment = (projectId: number, formData: FormData) => {
  return api.post(`/admin/projects/${projectId}/payments`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
};

export const getPaymentStatus = (projectId: number) => {
  return api.get(`/admin/projects/${projectId}/payments/status`);
};

export const getPaymentHistory = (projectId: number, perPage: number = 15) => {
  return api.get(`/admin/projects/${projectId}/payments/history`, {
    params: { per_page: perPage },
  });
};
