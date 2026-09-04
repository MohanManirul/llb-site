import TextInput, { TextInputProps } from './TextInput';

export type EmailInputProps = Omit<TextInputProps, 'type'>;

export default function EmailInput(props: EmailInputProps) {
    return <TextInput autoComplete="email" {...props} type="email" />;
}
