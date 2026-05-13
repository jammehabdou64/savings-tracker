import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import GoalController from '@/actions/App/Http/Controllers/GoalController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Goal } from '@/types/savings';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    goal?: Goal | null;
};

export function GoalFormModal({ open, onOpenChange, goal }: Props) {
    const isEdit = Boolean(goal);

    const form = useForm({
        name: '',
        target: '',
        deadline: '',
    });

    useEffect(() => {
        if (open) {
            form.setData({
                name: goal?.name ?? '',
                target: goal ? String(goal.target) : '',
                deadline: goal?.deadline ?? '',
            });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, goal?.id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit && goal) {
            form.put(GoalController.update(goal.id).url, {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            });
        } else {
            form.post(GoalController.store().url, {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEdit ? 'Edit goal' : 'Create a goal'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Update the name, target amount, or deadline.'
                            : 'Set a name, target amount, and optional deadline.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="goal-name">Goal name</Label>
                        <Input
                            id="goal-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. New laptop"
                            required
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="goal-target">Target amount ($)</Label>
                        <Input
                            id="goal-target"
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={form.data.target}
                            onChange={(e) => form.setData('target', e.target.value)}
                            placeholder="1000"
                            required
                        />
                        <InputError message={form.errors.target} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="goal-deadline">Deadline (optional)</Label>
                        <Input
                            id="goal-deadline"
                            type="date"
                            value={form.data.deadline}
                            onChange={(e) => form.setData('deadline', e.target.value)}
                        />
                        <InputError message={form.errors.deadline} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {isEdit ? 'Save changes' : 'Create goal'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
