import { FormEvent, useEffect, useState } from 'react';
import { Button, Modal, TextInput, Toggle } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { AcademicSession, SessionFormData } from '../types';

interface SessionFormModalProps {
    show: boolean;
    session: AcademicSession | null;
    onClose: () => void;
    onSaved: () => void;
}

const EMPTY: SessionFormData = { label: '', is_current: false, is_active: true };

const LABEL_PATTERN = /^(\d{4})-(\d{2})$/;

export default function SessionFormModal({ show, session, onClose, onSaved }: SessionFormModalProps) {
    const [data, setData] = useState<SessionFormData>(EMPTY);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!show) return;

        setErrors({});
        setData(
            session
                ? { label: session.label, is_current: session.is_current, is_active: session.is_active }
                : EMPTY,
        );
    }, [show, session]);

    const submit = async (event: FormEvent) => {
        event.preventDefault();

        const match = LABEL_PATTERN.exec(data.label.trim());

        if (!match) {
            setErrors({ label: 'Use the 2024-25 format.' });
            return;
        }

        const startYear = Number(match[1]);

        setSaving(true);
        setErrors({});

        const payload = {
            label: data.label.trim(),
            start_year: startYear,
            end_year: startYear + 1,
            is_current: data.is_current,
            is_active: data.is_active,
        };

        try {
            if (session) {
                await api.put(`/admin/academic-sessions/${session.id}`, payload);
                flash.success('Session updated successfully.');
            } else {
                await api.post('/admin/academic-sessions', payload);
                flash.success('Session created successfully.');
            }

            onSaved();
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the session.'));
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="sm" label={session ? 'Edit session' : 'Create session'}>
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-semibold text-ink">
                    {session ? 'Edit session' : 'Create session'}
                </h2>

                <div className="mt-4 space-y-4">
                    <TextInput
                        label="Session"
                        placeholder="2024-25"
                        value={data.label}
                        onChange={(e) => setData((current) => ({ ...current, label: e.target.value }))}
                        error={errors.label}
                        hint="Academic year, e.g. 2024-25."
                        required
                    />

                    <Toggle
                        checked={data.is_current}
                        onChange={(checked) => setData((current) => ({ ...current, is_current: checked }))}
                        label="This is the current session"
                    />

                    <Toggle
                        checked={data.is_active}
                        onChange={(checked) => setData((current) => ({ ...current, is_active: checked }))}
                        label="Active"
                    />
                </div>

                <div className="mt-6 flex items-center justify-end gap-3">
                    <Button type="button" variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={saving}>
                        {session ? 'Save changes' : 'Create session'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
