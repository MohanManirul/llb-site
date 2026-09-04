<?php

namespace App\Http\Requests\Payment;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('super-admin') ||
               $user->hasPermissionTo('manage payments');
    }

    public function rules(): array
    {
        $paymentType = $this->input('payment_type');
        $projectId = $this->route('projectId') ?? $this->route('id');
        $project = $projectId ? Project::find($projectId) : null;

        $baseRules = [
            'payment_type' => ['required', 'string', 'in:cash,bank_transfer,cheque,card,mobile_wallet,ssl_gateway,other'],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                $project ? Rule::lte($project->package_amount) : '',
            ],
            'payment_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'next_payment_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,webp,pdf', 'max:5120'],
        ];

        $typeRules = match ($paymentType) {
            'cash' => [
                'reference_number' => ['nullable', 'string', 'max:100'],
            ],
            'bank_transfer' => [
                'reference_number' => ['required', 'string', 'max:100'],
                'bank_name' => ['required', 'string', 'max:100'],
            ],
            'cheque' => [
                'reference_number' => ['required', 'string', 'max:100'],
                'cheque_number' => ['required', 'string', 'max:50'],
            ],
            'card' => [
                'reference_number' => ['required', 'string', 'max:100'],
                'card_last_four' => ['nullable', 'string', 'max:4'],
            ],
            'mobile_wallet' => [
                'reference_number' => ['required', 'string', 'max:100'],
                'wallet_type' => ['required', 'string', 'in:bkash,nagad,rocket'],
            ],
            'ssl_gateway' => [
                'reference_number' => ['required', 'string', 'max:100'],
            ],
            'other' => [
                'reference_number' => ['nullable', 'string', 'max:100'],
                'payment_method' => ['required', 'string', 'max:100'],
            ],
            default => [],
        };

        return array_merge($baseRules, $typeRules);
    }

    public function messages(): array
    {
        $projectId = $this->route('projectId') ?? $this->route('id');
        $project = $projectId ? Project::find($projectId) : null;

        return [
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a number',
            'amount.lte' => $project
                ? "Payment amount cannot exceed package amount ({$project->package_amount})"
                : 'Payment amount exceeds package amount',
            'payment_date.required' => 'Payment date is required',
            'payment_date.date' => 'Payment date must be a valid date',
            'payment_date.date_format' => 'Payment date format must be YYYY-MM-DD',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future',
            'next_payment_date.date' => 'Next payment date must be a valid date',
            'next_payment_date.date_format' => 'Next payment date format must be YYYY-MM-DD',
            'next_payment_date.after' => 'Next payment date must be in the future',
            'proof.mimes' => 'Proof must be an image (JPEG, PNG, GIF, WebP) or PDF file',
            'proof.max' => 'Proof file cannot be larger than 5 MB',
        ];
    }
}
