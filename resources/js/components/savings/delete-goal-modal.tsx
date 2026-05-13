import { router } from '@inertiajs/react';
import { useState } from 'react';
import GoalController from '@/actions/App/Http/Controllers/GoalController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Goal } from '@/types/savings';

type Props = {
    goal: Goal | null;
    onOpenChange: (open: boolean) => void;
    onDeleted?: () => void;
};

export function DeleteGoalModal({ goal, onOpenChange, onDeleted }: Props) {
    const [processing, setProcessing] = useState(false);

    const handleDelete = () => {
        if (!goal) return;

        setProcessing(true);
        router.delete(GoalController.destroy(goal.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false);
                onDeleted?.();
            },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <Dialog open={goal !== null} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete this goal?</DialogTitle>
                    <DialogDescription>
                        This will permanently delete{' '}
                        <span className="font-semibold">{goal?.name}</span> and
                        its entire deposit history. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={handleDelete}
                        disabled={processing}
                    >
                        Delete goal
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
