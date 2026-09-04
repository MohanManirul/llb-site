import { useState } from 'react';
import { createPayment } from '@/pages/admin/payment/api';
import { PaymentType } from '@/pages/admin/payment/types';
import { CURRENCY_SYMBOL, formatMoney } from '@/lib/format';
import {
  Button,
  Modal,
  TextInput,
  SelectInput,
  Textarea,
  CalendarInput,
} from '@/components/ui';

interface ProjectSummary {
  package_amount?: number | string | null;
  amount_paid?: number | string | null;
  amount_due?: number | string | null;
}

interface PaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  projectId: number;
  paymentTypes: PaymentType[];
  project?: ProjectSummary;
  onPaymentSuccess?: () => void;
}

export default function PaymentModal({
  isOpen,
  onClose,
  projectId,
  paymentTypes,
  project,
  onPaymentSuccess,
}: PaymentModalProps) {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const defaultType = paymentTypes[0]?.value || 'cash';

  const [formData, setFormData] = useState({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    next_payment_date: '',
    reference_number: '',
    notes: '',
    payment_type: defaultType,
    bank_name: '',
    cheque_number: '',
    card_last_four: '',
    wallet_type: '',
    payment_method: '',
    proof: null as File | null,
  });

  const handleInputChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>
  ) => {
    let { name, value } = e.target;

    // Only allow digits for card_last_four
    if (name === 'card_last_four') {
      value = value.replace(/\D/g, '');
    }

    // Validate amount doesn't exceed amount_due
    if (name === 'amount' && project?.amount_due) {
      const amount = parseFloat(value);
      const maxAmount = parseFloat(String(project.amount_due));

      if (!isNaN(amount) && amount > maxAmount) {
        value = String(maxAmount);
      }
    }

    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleDateChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData(prev => ({ ...prev, payment_date: e.target.value }));
  };

  const handleFileChange = (file: File | null) => {
    if (file) {
      const maxSizeMB = 2;
      const maxSizeBytes = maxSizeMB * 1024 * 1024;

      if (file.size > maxSizeBytes) {
        setError(`File size exceeds ${maxSizeMB}MB limit`);
        return;
      }

      const validFileTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!validFileTypes.includes(file.type)) {
        setError('Only image files (JPEG, PNG, WebP) are allowed');
        return;
      }

      setError(null);
    }

    setFormData(prev => ({ ...prev, proof: file }));
  };

  const handleCloseModal = () => {
    setSuccess(false);
    setSuccessMessage(null);
    setError(null);
    setFormData({
      amount: '',
      payment_date: new Date().toISOString().split('T')[0],
      next_payment_date: '',
      reference_number: '',
      notes: '',
      payment_type: defaultType,
      bank_name: '',
      cheque_number: '',
      card_last_four: '',
      wallet_type: '',
      payment_method: '',
      proof: null,
    });
    onClose();
  };

  const remainingDue = (() => {
    const due = parseFloat(String(project?.amount_due || 0));
    const amount = parseFloat(formData.amount || '0');
    return due - amount;
  })();

  const isDuePaid = remainingDue <= 0;

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError(null);

    const amount = parseFloat(formData.amount);
    const packageAmount = parseFloat(String(project?.package_amount || 0));

    // Validate amount
    if (!amount || isNaN(amount)) {
      setError('Please enter a valid payment amount');
      return;
    }

    if (amount <= 0) {
      setError('Payment amount must be greater than 0');
      return;
    }

    if (amount > packageAmount) {
      setError(`Payment amount cannot exceed package amount (${formatMoney(packageAmount)})`);
      return;
    }

    // Validate payment type specific fields
    if (formData.payment_type === 'bank_transfer') {
      if (!formData.bank_name?.trim()) {
        setError('Bank name is required for bank transfer');
        return;
      }
      if (!formData.reference_number?.trim()) {
        setError('Reference number is required for bank transfer');
        return;
      }
    }

    if (formData.payment_type === 'cheque') {
      if (!formData.cheque_number?.trim()) {
        setError('Cheque number is required');
        return;
      }
      if (!formData.reference_number?.trim()) {
        setError('Reference number is required for cheque payment');
        return;
      }
    }

    if (formData.payment_type === 'card') {
      if (formData.card_last_four && formData.card_last_four.length !== 4) {
        setError('Card last 4 digits must be exactly 4 characters');
        return;
      }
      if (formData.card_last_four && !/^\d+$/.test(formData.card_last_four)) {
        setError('Card last 4 digits must contain only numbers');
        return;
      }
      if (!formData.reference_number?.trim()) {
        setError('Reference number is required for card payment');
        return;
      }
    }

    if (formData.payment_type === 'mobile_wallet') {
      if (!formData.wallet_type?.trim()) {
        setError('Please select a wallet type');
        return;
      }
      if (!formData.reference_number?.trim()) {
        setError('Reference number is required for mobile wallet payment');
        return;
      }
    }

    if (formData.payment_type === 'other') {
      if (!formData.payment_method?.trim()) {
        setError('Payment method is required');
        return;
      }
    }

    setIsLoading(true);

    try {
      const form = new FormData();
      form.append('payment_type', formData.payment_type);
      form.append('amount', formData.amount);
      form.append('payment_date', formData.payment_date);
      form.append('notes', formData.notes);

      if (formData.next_payment_date) {
        form.append('next_payment_date', formData.next_payment_date);
      }

      if (formData.reference_number) {
        form.append('reference_number', formData.reference_number);
      }
      if (formData.bank_name) {
        form.append('bank_name', formData.bank_name);
      }
      if (formData.cheque_number) {
        form.append('cheque_number', formData.cheque_number);
      }
      if (formData.card_last_four) {
        form.append('card_last_four', formData.card_last_four);
      }
      if (formData.wallet_type) {
        form.append('wallet_type', formData.wallet_type);
      }
      if (formData.payment_method) {
        form.append('payment_method', formData.payment_method);
      }
      if (formData.proof) {
        form.append('proof', formData.proof);
      }

      await createPayment(projectId, form);

      const paymentAmount = parseFloat(formData.amount);
      const remainingDue = parseFloat(String(project?.amount_due || 0)) - paymentAmount;

      if (remainingDue <= 0) {
        setSuccessMessage('✅ Payment recorded successfully! Project is fully paid.');
      } else {
        setSuccessMessage(`✅ Payment of ${formatMoney(paymentAmount)} recorded successfully!`);
      }

      setSuccess(true);

      setTimeout(() => {
        onPaymentSuccess?.();
        handleCloseModal();
      }, 1500);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to record payment');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Modal show={isOpen} onClose={onClose} maxWidth="2xl">
      <div className="p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold">Payment Collection</h2>
          <button
            onClick={handleCloseModal}
            className="text-red-600 hover:text-red-800 hover:bg-red-50 rounded-full p-1 transition-colors"
            type="button"
            aria-label="Close modal"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {project && (
          <div className="mb-6 flex gap-4 text-sm">
            <div className="flex-1 p-4 bg-blue-50 rounded-lg">
              <div className="text-gray-500 mb-1">Total</div>
              <div className="font-semibold text-gray-900">{formatMoney(project.package_amount)}</div>
            </div>
            <div className="flex-1 p-4 bg-green-50 rounded-lg">
              <div className="text-gray-500 mb-1">Paid</div>
              <div className="font-semibold text-green-600">
                {formatMoney(
                  parseFloat(String(project.amount_paid || 0)) +
                    parseFloat(formData.amount || '0')
                )}
              </div>
            </div>
            {!isDuePaid && (
              <div className="flex-1 p-4 bg-red-50 rounded-lg">
                <div className="text-gray-500 mb-1">Due</div>
                <div className="font-semibold text-red-600">
                  {formatMoney(remainingDue)}
                </div>
              </div>
            )}
          </div>
        )}

        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded">
            {error}
          </div>
        )}

        {success && successMessage && (
          <div className="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
            <div className="font-semibold text-lg">{successMessage}</div>
            {parseFloat(String(project?.amount_due || 0)) - parseFloat(formData.amount || '0') <= 0 && (
              <div className="mt-2 text-sm font-semibold text-green-800">
                💰 Full Payment Completed
              </div>
            )}
          </div>
        )}

        {!success && (
          <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <SelectInput
              label="Payment Type"
              name="payment_type"
              value={formData.payment_type}
              onChange={handleInputChange}
              required
            >
              {paymentTypes.map(pt => (
                <option key={pt.value} value={pt.value}>
                  {pt.label}
                </option>
              ))}
            </SelectInput>

            <TextInput
              type="text"
              label="Amount"
              name="amount"
              value={formData.amount}
              onChange={handleInputChange}
              placeholder="0.00"
              step="0.01"
              min="0.01"
              max={project?.amount_due ? String(project.amount_due) : undefined}
              required
            />
          </div>

          <div className={`grid gap-4 ${isDuePaid ? 'grid-cols-1' : 'grid-cols-2'}`}>
            <CalendarInput
              label="Payment Date (Today Only)"
              name="payment_date"
              value={formData.payment_date}
              onChange={handleDateChange}
              max={new Date().toISOString().split('T')[0]}
              min={new Date().toISOString().split('T')[0]}
            />

            {!isDuePaid && (
              <CalendarInput
                label="Next Payment Date (Optional)"
                name="next_payment_date"
                value={formData.next_payment_date}
                onChange={(e) => setFormData(prev => ({ ...prev, next_payment_date: e.target.value }))}
                min={new Date(new Date().getTime() + 86400000).toISOString().split('T')[0]}
              />
            )}
          </div>

          {formData.payment_type === 'bank_transfer' && (
            <div className="space-y-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
              <div className="grid grid-cols-2 gap-4">
                <TextInput
                  label="Bank Name"
                  name="bank_name"
                  value={formData.bank_name}
                  onChange={handleInputChange}
                  required
                />
                <TextInput
                  label="Reference Number"
                  name="reference_number"
                  value={formData.reference_number}
                  onChange={handleInputChange}
                  required
                />
              </div>
            </div>
          )}

          {formData.payment_type === 'cheque' && (
            <div className="space-y-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
              <div className="grid grid-cols-2 gap-4">
                <TextInput
                  label="Cheque Number"
                  name="cheque_number"
                  value={formData.cheque_number}
                  onChange={handleInputChange}
                  required
                />
                <TextInput
                  label="Reference Number"
                  name="reference_number"
                  value={formData.reference_number}
                  onChange={handleInputChange}
                  required
                />
              </div>
            </div>
          )}

          {formData.payment_type === 'card' && (
            <div className="space-y-4 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
              <div className="grid grid-cols-2 gap-4">
                <TextInput
                  label="Last 4 Digits of Card"
                  name="card_last_four"
                  value={formData.card_last_four}
                  onChange={handleInputChange}
                  maxLength={4}
                />
                <TextInput
                  label="Reference Number"
                  name="reference_number"
                  value={formData.reference_number}
                  onChange={handleInputChange}
                  required
                />
              </div>
            </div>
          )}

          {formData.payment_type === 'mobile_wallet' && (
            <div className="space-y-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
              <SelectInput
                label="Wallet Type"
                name="wallet_type"
                value={formData.wallet_type}
                onChange={handleInputChange}
                required
              >
                <option value="">Select Wallet</option>
                {paymentTypes.find(pt => pt.value === 'mobile_wallet')?.options?.map(opt => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                )) || (
                  <>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="rocket">Rocket</option>
                  </>
                )}
              </SelectInput>

              <div className="grid grid-cols-1 gap-4">
                <TextInput
                  label="Reference Number"
                  name="reference_number"
                  value={formData.reference_number}
                  onChange={handleInputChange}
                  required
                />
              </div>
            </div>
          )}

          {formData.payment_type === 'other' && (
            <TextInput
              label="Payment Method"
              name="payment_method"
              value={formData.payment_method}
              onChange={handleInputChange}
              required
            />
          )}

          <Textarea
            label="Notes"
            name="notes"
            value={formData.notes}
            onChange={handleInputChange}
            placeholder="Add additional notes..."
          />

          <label className="flex items-center gap-4 p-4 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 cursor-pointer transition">
            <div className="shrink-0 flex items-center justify-center h-10 w-10 rounded border-2 border-dashed border-gray-300 bg-white">
              <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div className="flex-1">
              <div className="text-sm font-medium text-gray-900">Upload Image</div>
              <div className="text-xs text-gray-500">JPG, PNG or WebP, max 2MB. Click the image to upload.</div>
            </div>
            <input
              type="file"
              name="proof"
              accept="image/jpeg,image/png,image/webp"
              onChange={(e) => handleFileChange(e.target.files?.[0] || null)}
              className="sr-only"
            />
          </label>

          <div className="flex gap-3 justify-end pt-4">
            <Button
              type="button"
              tone="muted"
              onClick={handleCloseModal}
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={
                isLoading ||
                !formData.amount ||
                !formData.payment_type ||
                (project && parseFloat(formData.amount) > parseFloat(String(project.package_amount || 0)))
              }
            >
              {isLoading ? 'Processing...' : `${CURRENCY_SYMBOL} Record Payment`}
            </Button>
          </div>
        </form>
        )}
      </div>
    </Modal>
  );
}
