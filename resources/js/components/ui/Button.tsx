import { AnchorHTMLAttributes, ButtonHTMLAttributes, ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import { ControlSize, Tone, textSizeClasses, toneTextClasses } from './tokens';

const baseClasses =
    'inline-flex items-center justify-center gap-2 font-medium ' +
    'whitespace-nowrap transition focus:outline-none disabled:opacity-50 ' +
    'disabled:cursor-not-allowed aria-disabled:pointer-events-none aria-disabled:opacity-50';

const boxClasses = 'rounded-lg border focus:ring-2';

const linkClasses = 'rounded focus:ring-2 focus:ring-offset-2';

const sizeClasses: Record<ControlSize, string> = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
};

const variantClasses = {
    primary:
        'border-transparent bg-brand-accent text-white hover:bg-brand-accent focus:ring-brand-accent/30',
    secondary:
        'border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-gray-200',
    danger:
        'border-transparent bg-red-600 text-white hover:bg-red-500 focus:ring-red-200',
    dark:
        'border-transparent bg-gray-800 text-white hover:bg-gray-700 focus:ring-gray-300',
};

export type ButtonVariant = keyof typeof variantClasses | 'link';
export type ButtonSize = ControlSize;
export type ButtonTone = Tone;

type ButtonAnchorProps = Omit<
    AnchorHTMLAttributes<HTMLAnchorElement>,
    'type' | 'onProgress' | 'onError' | 'onCancel'
>;

interface ButtonBaseProps {
    variant?: ButtonVariant;
    size?: ButtonSize;
    tone?: ButtonTone;
    loading?: boolean;
    fullWidth?: boolean;
    disabled?: boolean;
    className?: string;
    children?: ReactNode;
}

export type ButtonProps =
    | (ButtonBaseProps &
          Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'disabled'> & { href?: never })
    | (ButtonBaseProps & ButtonAnchorProps & { href: string });

function isPlainAnchor(href: string): boolean {
    return (
        href.startsWith('#') ||
        href.startsWith('mailto:') ||
        href.startsWith('tel:') ||
        /^https?:\/\//.test(href)
    );
}

export default function Button({
    variant = 'primary',
    size = 'md',
    tone = 'default',
    loading = false,
    fullWidth = false,
    disabled = false,
    className = '',
    children,
    href,
    ...props
}: ButtonProps) {
    const isLink = variant === 'link';
    const isDisabled = disabled || loading;

    const shapeClasses = isLink
        ? `${linkClasses} ${textSizeClasses[size]} ${toneTextClasses[tone]}`
        : `${boxClasses} ${sizeClasses[size]} ${variantClasses[variant]}`;

    const classes = `${baseClasses} ${shapeClasses} ${
        fullWidth ? 'w-full' : ''
    } ${className}`;

    const content = (
        <>
            {loading && <ArrowPathIcon className="h-4 w-4 shrink-0 animate-spin" />}
            {children}
        </>
    );

    if (href !== undefined) {
        const anchorProps = props as ButtonAnchorProps;

        if (isDisabled) {
            return (
                <span className={classes} aria-disabled="true" role="link">
                    {content}
                </span>
            );
        }

        if (isPlainAnchor(href)) {
            const external = /^https?:\/\//.test(href);

            return (
                <a
                    href={href}
                    {...(external
                        ? { target: '_blank', rel: 'noopener noreferrer' }
                        : {})}
                    className={classes}
                    {...anchorProps}
                >
                    {content}
                </a>
            );
        }

        return (
            <Link href={href} className={classes} {...anchorProps}>
                {content}
            </Link>
        );
    }

    const { type = 'button', ...buttonProps } =
        props as ButtonHTMLAttributes<HTMLButtonElement>;

    return (
        <button
            type={type}
            className={classes}
            disabled={isDisabled}
            aria-busy={loading || undefined}
            {...buttonProps}
        >
            {content}
        </button>
    );
}
