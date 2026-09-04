import { ConfirmationModal } from '@/components/ui';
import { businessStatusLabel } from '@/config/businessStatus';
import type { PendingStatus } from '../../types';

interface StatusChangeModalProps {
    pending: PendingStatus | null;
    saving: boolean;
    onClose: () => void;
    onConfirm: () => void;
}

export default function StatusChangeModal({
    pending,
    saving,
    onClose,
    onConfirm,
}: StatusChangeModalProps) {
    return (
        <ConfirmationModal
            show={pending !== null}
            onClose={onClose}
            onConfirm={onConfirm}
            processing={saving}
            title="Update project status?"
            confirmText="Yes, update"
        >
            {pending && (
                <>
                    Are you sure you want to change the status of{' '}
                    <span className="font-medium text-gray-900">
                        {pending.project.business_name}
                    </span>{' '}
                    from{' '}
                    <span className="font-medium text-gray-900">
                        {businessStatusLabel(pending.project.business_status)}
                    </span>{' '}
                    to{' '}
                    <span className="font-medium text-gray-900">
                        {pending.label}
                    </span>
                    ?
                </>
            )}
        </ConfirmationModal>
    );
}
