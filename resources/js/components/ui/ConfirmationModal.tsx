import { ReactNode, useId } from 'react';
import {
    ExclamationTriangleIcon,
    QuestionMarkCircleIcon,
} from '@heroicons/react/24/outline';
import Modal from './Modal';
import Button from './Button';

const variantStyles = {
    danger: {
        iconWrapper: 'bg-red-100',
        icon: 'text-red-600',
        Icon: ExclamationTriangleIcon,
        confirmVariant: 'danger' as const,
    },
    primary: {
        iconWrapper: 'bg-brand-accent/10',
        icon: 'text-brand-accent',
        Icon: QuestionMarkCircleIcon,
        confirmVariant: 'primary' as const,
    },
};

export type ConfirmationModalVariant = keyof typeof variantStyles;

export interface ConfirmationModalProps {
    show?: boolean;
    onClose?: () => void;
    onConfirm?: () => void;
    processing?: boolean;
    variant?: ConfirmationModalVariant;
    title?: string;
    confirmText?: string;
    cancelText?: string;
    children?: ReactNode;
}

export default function ConfirmationModal({
    show = false,
    onClose,
    onConfirm,
    processing = false,
    variant = 'danger',
    title,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    children,
}: ConfirmationModalProps) {
    const titleId = useId();
    const style = variantStyles[variant];
    const { Icon } = style;

    return (
        <Modal
            show={show}
            onClose={onClose}
            maxWidth="md"
            labelledBy={title ? titleId : undefined}
            label={title ? undefined : confirmText}
        >
            <div className="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                    <div
                        className={`mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10 ${style.iconWrapper}`}
                    >
                        <Icon className={`h-6 w-6 ${style.icon}`} aria-hidden="true" />
                    </div>

                    <div className="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3
                            id={titleId}
                            className="text-lg font-medium text-gray-900"
                        >
                            {title}
                        </h3>
                        <div className="mt-2 text-sm text-gray-600">{children}</div>
                    </div>
                </div>
            </div>

            <div className="flex flex-row justify-end gap-3 bg-gray-100 px-6 py-4">
                <Button
                    variant="secondary"
                    onClick={onClose}
                    disabled={processing}
                >
                    {cancelText}
                </Button>
                <Button
                    variant={style.confirmVariant}
                    onClick={onConfirm}
                    loading={processing}
                >
                    {confirmText}
                </Button>
            </div>
        </Modal>
    );
}
