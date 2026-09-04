export interface Payment {
  id: string;
  project_id: number;
  payment_type: string;
  amount: number;
  payment_date: string;
  reference_number: string | null;
  proof: string | null;
  notes: string | null;
  created_by_name: string | null;
  created_at: string;
}

export interface PaymentStatus {
  total_amount: number;
  paid_amount: number;
  due_amount: number;
  next_payment_date: string | null;
  last_payment_date: string | null;
  payment_count: number;
}

export interface PaymentHistory {
  id: string;
  project_id: number;
  payment_id: string | null;
  action: string;
  changed_amount: number | null;
  old_paid_amount: number | null;
  new_paid_amount: number | null;
  changed_by_name: string | null;
  created_at: string;
}

export interface PaymentType {
  value: string;
  label: string;
  options?: Array<{ value: string; label: string }>;
}
