import { useCallback, useEffect, useState } from 'react';
import {
    ChatBubbleLeftRightIcon,
    PencilSquareIcon,
    PlusIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import { Modal, Button, Textarea, ConfirmationModal } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';
import { formatDateTime } from '@/lib/format';
import type { ProjectNote } from '../types';

interface NotesSubject {
    id: number;
    business_name?: string | null;
}

interface ProjectNotesModalProps {
    show: boolean;
    onClose?: () => void;
    project: NotesSubject | null;
    canAdd?: boolean;
}

export default function ProjectNotesModal({
    show,
    onClose,
    project,
    canAdd = true,
}: ProjectNotesModalProps) {
    const [notes, setNotes] = useState<ProjectNote[]>([]);
    const [loading, setLoading] = useState(false);

    const [showAddForm, setShowAddForm] = useState(false);
    const [noteText, setNoteText] = useState('');
    const [noteError, setNoteError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const [editingId, setEditingId] = useState<number | null>(null);
    const [editText, setEditText] = useState('');
    const [editError, setEditError] = useState<string | null>(null);
    const [editSubmitting, setEditSubmitting] = useState(false);

    const [deletingNote, setDeletingNote] = useState<ProjectNote | null>(null);
    const [deleteSubmitting, setDeleteSubmitting] = useState(false);

    useEffect(() => {
        if (show) {
            setShowAddForm(false);
            setNoteText('');
            setNoteError(null);

            setEditingId(null);
            setEditText('');
            setEditError(null);

            setDeletingNote(null);
        }
    }, [show]);

    const fetchNotes = useCallback(async () => {
        if (!show || !project?.id) {
            return;
        }

        setEditingId(null);
        setEditText('');
        setEditError(null);

        setLoading(true);

        try {
            const { data } = await api.get(
                `/admin/projects/${project.id}/notes`,
            );

            setNotes(data?.result?.data ?? []);
        } catch (error) {
            flash.error(errorMessage(error, 'Could not load notes.'));
        } finally {
            setLoading(false);
        }
    }, [show, project?.id]);

    useEffect(() => {
        fetchNotes();
    }, [fetchNotes]);

    function openAddForm() {
        setEditingId(null);
        setEditText('');
        setEditError(null);

        setNoteText('');
        setNoteError(null);

        setShowAddForm(true);
    }

    function cancelAdd() {
        if (submitting) {
            return;
        }

        setShowAddForm(false);
        setNoteText('');
        setNoteError(null);
    }

    async function submitNote(e: React.FormEvent) {
        e.preventDefault();

        if (!noteText.trim()) {
            setNoteError('Please enter a note.');
            return;
        }

        if (!project?.id) {
            return;
        }

        setSubmitting(true);
        setNoteError(null);

        try {
            await api.post(`/admin/projects/${project.id}/notes`, {
                note: noteText,
            });

            flash.success('Note added successfully.');

            setNoteText('');
            setNoteError(null);
            setShowAddForm(false);

            await fetchNotes();
        } catch (error) {
            if (
                (error as { response?: { status?: number } })?.response
                    ?.status === 422
            ) {
                setNoteError(validationErrors(error).note);
            } else {
                flash.error(
                    errorMessage(error, 'Could not add the note.'),
                );
            }
        } finally {
            setSubmitting(false);
        }
    }

    function startEdit(note: ProjectNote) {
        setShowAddForm(false);

        setNoteText('');
        setNoteError(null);

        setEditingId(note.id);
        setEditText(note.note);
        setEditError(null);
    }

    function cancelEdit() {
        if (editSubmitting) {
            return;
        }

        setEditingId(null);
        setEditText('');
        setEditError(null);
    }

    async function saveEdit(e: React.FormEvent) {
        e.preventDefault();

        if (!editText.trim()) {
            setEditError('Please enter a note.');
            return;
        }

        if (!project?.id || editingId === null) {
            return;
        }

        setEditSubmitting(true);
        setEditError(null);

        try {
            const { data } = await api.patch(
                `/admin/projects/${project.id}/notes/${editingId}`,
                {
                    note: editText,
                },
            );

            const updated = data?.result;

            setNotes((current) =>
                current.map((note) =>
                    note.id === editingId
                        ? {
                              ...note,
                              note: updated?.note ?? editText,
                          }
                        : note,
                ),
            );

            flash.success('Note updated successfully.');

            cancelEdit();
        } catch (error) {
            if (
                (error as { response?: { status?: number } })?.response
                    ?.status === 422
            ) {
                setEditError(validationErrors(error).note);
            } else {
                flash.error(
                    errorMessage(error, 'Could not update the note.'),
                );
            }
        } finally {
            setEditSubmitting(false);
        }
    }

    async function confirmDelete() {
        if (!deletingNote || !project?.id) {
            return;
        }

        setDeleteSubmitting(true);

        try {
            await api.delete(
                `/admin/projects/${project.id}/notes/${deletingNote.id}`,
            );

            setNotes((current) =>
                current.filter((note) => note.id !== deletingNote.id),
            );

            flash.success('Note deleted successfully.');

            setDeletingNote(null);
        } catch (error) {
            flash.error(
                errorMessage(error, 'Could not delete the note.'),
            );
        } finally {
            setDeleteSubmitting(false);
        }
    }

    const isEditing = editingId !== null;

    return (
        <>
            <Modal
                show={show}
                onClose={() => {
                    if (!deletingNote && !submitting && !editSubmitting) {
                        onClose?.();
                    }
                }}
                maxWidth="2xl"
            >
                <div className="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-4 sm:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-brand-accent text-white">
                            <ChatBubbleLeftRightIcon className="h-5 w-5" />
                        </span>

                        <div className="min-w-0">
                            <h3 className="text-lg font-semibold text-gray-800">
                                Notes
                            </h3>

                            {project?.business_name && (
                                <p className="truncate text-xs text-gray-500">
                                    {project.business_name}
                                </p>
                            )}
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close"
                        disabled={submitting || editSubmitting}
                        className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <XMarkIcon className="h-5 w-5" />
                    </button>
                </div>

                <div className="max-h-[75vh] overflow-y-auto px-4 py-5 sm:px-6">
                    {canAdd && (
                        <div className="mb-5">
                            {!showAddForm && !isEditing ? (
                                <Button
                                    size="sm"
                                    onClick={openAddForm}
                                >
                                    <PlusIcon className="h-4 w-4" />
                                    Add Note
                                </Button>
                            ) : (
                                <form
                                    onSubmit={
                                        isEditing
                                            ? saveEdit
                                            : submitNote
                                    }
                                    className={
                                        'rounded-card border p-4 ' +
                                        (isEditing
                                            ? 'border-indigo-200 bg-indigo-50/50'
                                            : 'border-gray-200 bg-gray-50')
                                    }
                                >
                                    <Textarea
                                        label={
                                            isEditing
                                                ? 'Edit Note'
                                                : 'Note'
                                        }
                                        name={
                                            isEditing
                                                ? 'edit-note'
                                                : 'note'
                                        }
                                        rows={3}
                                        placeholder="Write a note…"
                                        value={
                                            isEditing
                                                ? editText
                                                : noteText
                                        }
                                        onChange={(e) => {
                                            if (isEditing) {
                                                setEditText(
                                                    e.target.value,
                                                );
                                                setEditError(null);
                                            } else {
                                                setNoteText(
                                                    e.target.value,
                                                );
                                                setNoteError(null);
                                            }
                                        }}
                                        error={
                                            isEditing
                                                ? editError
                                                : noteError
                                        }
                                        required
                                        disabled={
                                            isEditing
                                                ? editSubmitting
                                                : submitting
                                        }
                                    />

                                    <div className="mt-3 flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            onClick={
                                                isEditing
                                                    ? cancelEdit
                                                    : cancelAdd
                                            }
                                            disabled={
                                                isEditing
                                                    ? editSubmitting
                                                    : submitting
                                            }
                                        >
                                            Cancel
                                        </Button>

                                        <Button
                                            type="submit"
                                            size="sm"
                                            loading={
                                                isEditing
                                                    ? editSubmitting
                                                    : submitting
                                            }
                                            disabled={
                                                isEditing
                                                    ? editSubmitting
                                                    : submitting
                                            }
                                        >
                                            {isEditing
                                                ? 'Update Note'
                                                : 'Save Note'}
                                        </Button>
                                    </div>
                                </form>
                            )}
                        </div>
                    )}

                    <div className="relative overflow-x-auto rounded-card border border-hairline">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-sidebar">
                                <tr className="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th className="w-14 px-4 py-3">
                                        SL
                                    </th>

                                    <th className="px-4 py-3">
                                        Note
                                    </th>

                                    <th className="px-4 py-3">
                                        Date
                                    </th>

                                    {canAdd && (
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    )}
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-gray-100">
                                {notes.length === 0 && !loading && (
                                    <tr>
                                        <td
                                            colSpan={canAdd ? 4 : 3}
                                            className="px-4 py-6 text-center text-gray-400"
                                        >
                                            No notes found.
                                        </td>
                                    </tr>
                                )}

                                {notes.map((note, index) => {
                                    const editing = editingId === note.id;

                                    return (
                                    <tr
                                        key={note.id}
                                        className={editing ? 'bg-indigo-50' : ''}
                                    >
                                        <td
                                            className={
                                                'px-4 py-3 text-gray-500 ' +
                                                (editing
                                                    ? 'border-l-2 border-brand-accent font-medium text-brand-accent'
                                                    : '')
                                            }
                                        >
                                            {index + 1}
                                        </td>

                                        <td className="max-w-md px-4 py-3 text-gray-800">
                                            <span className="whitespace-pre-wrap">
                                                {note.note}
                                            </span>
                                        </td>

                                        <td className="whitespace-nowrap px-4 py-3 text-gray-500">
                                            {formatDateTime(note.created_at)}
                                        </td>

                                        {canAdd && (
                                            <td className="whitespace-nowrap px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-3">
                                                    {editing ? (
                                                        <span className="rounded-chip bg-brand-accent/10 px-2 py-0.5 text-xs font-medium text-brand-accent">
                                                            Editing
                                                        </span>
                                                    ) : (
                                                        <Button
                                                            variant="link"
                                                            tone="default"
                                                            size="sm"
                                                            onClick={() =>
                                                                startEdit(note)
                                                            }
                                                            disabled={
                                                                editingId !==
                                                                    null ||
                                                                submitting ||
                                                                editSubmitting
                                                            }
                                                        >
                                                            <PencilSquareIcon className="h-3.5 w-3.5" />
                                                            Edit
                                                        </Button>
                                                    )}

                                                    <Button
                                                        variant="link"
                                                        tone="danger"
                                                        size="sm"
                                                        onClick={() =>
                                                            setDeletingNote(note)
                                                        }
                                                        disabled={
                                                            submitting ||
                                                            editSubmitting
                                                        }
                                                    >
                                                        <TrashIcon className="h-3.5 w-3.5" />
                                                        Delete
                                                    </Button>
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {loading && (
                            <div className="absolute inset-0 flex items-center justify-center bg-white/60">
                                <span className="text-xs text-gray-500">
                                    Loading…
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </Modal>

            <ConfirmationModal
                show={deletingNote !== null}
                onClose={() => {
                    if (!deleteSubmitting) {
                        setDeletingNote(null);
                    }
                }}
                onConfirm={confirmDelete}
                processing={deleteSubmitting}
                title="Delete Note"
                confirmText={
                    deleteSubmitting ? 'Deleting…' : 'Delete'
                }
            >
                Are you sure you want to delete this note? This action
                cannot be undone.
            </ConfirmationModal>
        </>
    );
}