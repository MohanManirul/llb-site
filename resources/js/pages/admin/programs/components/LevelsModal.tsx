import { FormEvent, useCallback, useEffect, useState } from 'react';
import { TrashIcon } from '@heroicons/react/24/outline';
import { Button, Modal, TextInput, NumberStepper } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { LevelFormData, Program, ProgramLevel } from '../types';

interface LevelsModalProps {
    show: boolean;
    program: Program | null;
    onClose: () => void;
    onChanged: () => void;
}

const EMPTY: LevelFormData = { position: 1, name_bn: '', name_en: '' };

export default function LevelsModal({ show, program, onClose, onChanged }: LevelsModalProps) {
    const [levels, setLevels] = useState<ProgramLevel[]>([]);
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState<LevelFormData>(EMPTY);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const load = useCallback(async () => {
        if (!program) return;

        setLoading(true);

        try {
            const { data } = await api.get<ApiEnvelope<Program>>(`/admin/programs/${program.id}`);
            const loaded = data.result.levels ?? [];

            setLevels(loaded);
            setForm({
                position: (loaded[loaded.length - 1]?.position ?? 0) + 1,
                name_bn: '',
                name_en: '',
            });
        } catch (error) {
            flash.error(errorMessage(error, 'Could not load levels.'));
        } finally {
            setLoading(false);
        }
    }, [program]);

    useEffect(() => {
        if (show) {
            setErrors({});
            load();
        }
    }, [show, load]);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!program) return;

        setSaving(true);
        setErrors({});

        try {
            await api.post('/admin/program-levels', {
                program_id: program.id,
                position: form.position,
                name_bn: form.name_bn,
                name_en: form.name_en,
                is_active: true,
            });

            flash.success('Level added.');
            onChanged();
            await load();
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not add the level.'));
            }
        } finally {
            setSaving(false);
        }
    };

    const removeLevel = async (level: ProgramLevel) => {
        setDeletingId(level.id);

        try {
            await api.delete(`/admin/program-levels/${level.id}`);
            flash.success('Level deleted.');
            onChanged();
            await load();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not delete this level.'));
        } finally {
            setDeletingId(null);
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="lg" label="Manage levels">
            <div className="p-6">
                <h2 className="text-lg font-semibold text-ink">
                    Levels — {program?.name_en}
                </h2>
                <p className="mt-1 text-sm text-ink-muted">
                    Years or parts of this program, in exam order.
                </p>

                <div className="mt-4 overflow-hidden rounded-control border border-hairline">
                    <table className="w-full text-sm">
                        <thead className="bg-field text-left text-xs font-medium text-ink-muted">
                            <tr>
                                <th className="px-3 py-2">#</th>
                                <th className="px-3 py-2">Name (Bangla)</th>
                                <th className="px-3 py-2">Name (English)</th>
                                <th className="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan={4} className="px-3 py-4 text-center text-ink-muted">
                                        Loading…
                                    </td>
                                </tr>
                            ) : levels.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-3 py-4 text-center text-ink-muted">
                                        No levels yet.
                                    </td>
                                </tr>
                            ) : (
                                levels.map((level) => (
                                    <tr key={level.id} className="border-t border-hairline">
                                        <td className="px-3 py-2">{level.position}</td>
                                        <td className="px-3 py-2">{level.name_bn}</td>
                                        <td className="px-3 py-2">{level.name_en}</td>
                                        <td className="px-3 py-2 text-right">
                                            <button
                                                type="button"
                                                onClick={() => removeLevel(level)}
                                                disabled={deletingId === level.id}
                                                className="inline-flex items-center gap-1 text-red-600 hover:text-red-800 disabled:opacity-50"
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <form onSubmit={submit} className="mt-5 rounded-control border border-hairline bg-field p-4">
                    <p className="text-sm font-medium text-ink">Add a level</p>

                    <div className="mt-3 grid gap-3 sm:grid-cols-[110px_1fr_1fr]">
                        <NumberStepper
                            label="Position"
                            value={String(form.position)}
                            onChange={(value) =>
                                setForm((current) => ({ ...current, position: Number(value) || 1 }))
                            }
                            min={1}
                            max={20}
                            error={errors.position}
                        />
                        <TextInput
                            label="Name (Bangla)"
                            placeholder="১ম বর্ষ"
                            value={form.name_bn}
                            onChange={(e) => setForm((current) => ({ ...current, name_bn: e.target.value }))}
                            error={errors.name_bn}
                            required
                        />
                        <TextInput
                            label="Name (English)"
                            placeholder="1st Year"
                            value={form.name_en}
                            onChange={(e) => setForm((current) => ({ ...current, name_en: e.target.value }))}
                            error={errors.name_en}
                            required
                        />
                    </div>

                    <div className="mt-4 flex justify-end">
                        <Button type="submit" size="sm" loading={saving}>
                            Add level
                        </Button>
                    </div>
                </form>

                <div className="mt-5 flex justify-end">
                    <Button type="button" variant="secondary" onClick={onClose}>
                        Close
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
