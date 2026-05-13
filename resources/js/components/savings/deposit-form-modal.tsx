import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import DepositController from '@/actions/App/Http/Controllers/DepositController';
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
    goal: Goal | null;
};

export function DepositFormModal({ open, onOpenChange, goal }: Props) {
    const form = useForm({
        amount: '',
        note: '',
    });

    useEffect(() => {
        if (open) {
            form.setData({ amount: '', note: '' });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, goal?.id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!goal) return;

        form.post(DepositController.store(goal.id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add a deposit</DialogTitle>
                    <DialogDescription>
                        Record a deposit toward{' '}
                        <span className="font-semibold">{goal?.name}</span>.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="deposit-amount">Amount ($)</Label>
                        <Input
                            id="deposit-amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={form.data.amount}
                            onChange={(e) => form.setData('amount', e.target.value)}
                            placeholder="100"
                            required
                            autoFocus
                        />
                        <InputError message={form.errors.amount} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="deposit-note">Note (optional)</Label>
                        <Input
                            id="deposit-note"
                            value={form.data.note}
                            onChange={(e) => form.setData('note', e.target.value)}
                            placeholder="e.g. Freelance bonus"
                            maxLength={255}
                        />
                        <InputError message={form.errors.note} />
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
                            Add deposit
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
