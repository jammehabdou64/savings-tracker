import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, CheckCircle2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DepositController from '@/actions/App/Http/Controllers/DepositController';
import { DeleteGoalModal } from '@/components/savings/delete-goal-modal';
import { DepositFormModal } from '@/components/savings/deposit-form-modal';
import { GoalFormModal } from '@/components/savings/goal-form-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/savings';
import { dashboard } from '@/routes';
import type { Goal } from '@/types/savings';

type Props = {
    goal: Required<Pick<Goal, 'deposits'>> & Goal;
};

export default function GoalShow({ goal }: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [depositOpen, setDepositOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const remaining = Math.max(0, goal.target - goal.saved);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: goal.name, href: '#' },
        ],
    });

    const removeDeposit = (depositId: number) => {
        if (!confirm('Remove this deposit?')) return;

        router.delete(DepositController.destroy([goal.id, depositId]).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={goal.name} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Link
                        href={dashboard()}
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to dashboard
                    </Link>

                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setEditOpen(true)}>
                            <Pencil className="size-4" />
                            Edit
                        </Button>
                        <Button
                            variant="outline"
                            className="text-destructive hover:text-destructive"
                            onClick={() => setDeleteOpen(true)}
                        >
                            <Trash2 className="size-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <Card className="p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight">
                                {goal.name}
                            </h1>
                            <p className="mt-2 inline-flex items-center gap-2 text-sm text-muted-foreground">
                                <CalendarDays className="size-4" />
                                {formatDate(goal.deadline)}
                            </p>
                        </div>

                        {goal.isCompleted && (
                            <Badge className="gap-1 bg-emerald-100 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <CheckCircle2 className="size-3" />
                                Goal complete
                            </Badge>
                        )}
                    </div>

                    {goal.isCompleted ? (
                        <div className="mt-6 grid gap-4 sm:grid-cols-2">
                            <Stat label="Total saved" value={formatCurrency(goal.saved)} />
                            <Stat
                                label="Total deposits"
                                value={`${goal.deposits.length}`}
                            />
                        </div>
                    ) : (
                        <div className="mt-6 space-y-4">
                            <div className="grid gap-4 sm:grid-cols-3">
                                <Stat
                                    label="Saved"
                                    value={formatCurrency(goal.saved)}
                                />
                                <Stat
                                    label="Target"
                                    value={formatCurrency(goal.target)}
                                />
                                <Stat
                                    label="Remaining"
                                    value={formatCurrency(remaining)}
                                />
                            </div>

                            <div>
                                <div className="mb-2 flex items-baseline justify-between text-sm">
                                    <span className="font-medium">Progress</span>
                                    <span className="text-lg font-semibold tabular-nums">
                                        {goal.progress.toFixed(0)}%
                                    </span>
                                </div>
                                <div className="h-3 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full rounded-full bg-primary transition-all"
                                        style={{
                                            width: `${Math.min(100, goal.progress)}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    )}
                </Card>

                <section>
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-xl font-semibold tracking-tight">
                            Deposit history
                        </h2>
                        <Button onClick={() => setDepositOpen(true)}>
                            <Plus className="size-4" />
                            Add deposit
                        </Button>
                    </div>

                    {goal.deposits.length === 0 ? (
                        <Card className="p-10 text-center text-sm text-muted-foreground">
                            No deposits yet — add your first deposit to start
                            tracking progress.
                        </Card>
                    ) : (
                        <Card className="divide-y overflow-hidden p-0">
                            {goal.deposits.map((deposit) => (
                                <div
                                    key={deposit.id}
                                    className="flex items-center justify-between gap-4 px-5 py-4"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {deposit.note || (
                                                <span className="text-muted-foreground">
                                                    Deposit
                                                </span>
                                            )}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {formatDate(deposit.createdAt)}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-lg font-semibold tabular-nums">
                                            +{formatCurrency(deposit.amount)}
                                        </span>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-muted-foreground hover:text-destructive"
                                            aria-label="Remove deposit"
                                            onClick={() =>
                                                removeDeposit(deposit.id)
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </Card>
                    )}
                </section>
            </div>

            <GoalFormModal
                open={editOpen}
                onOpenChange={setEditOpen}
                goal={goal}
            />
            <DepositFormModal
                open={depositOpen}
                onOpenChange={setDepositOpen}
                goal={goal}
            />
            <DeleteGoalModal
                goal={deleteOpen ? goal : null}
                onOpenChange={(open) => !open && setDeleteOpen(false)}
            />
        </>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-xs uppercase tracking-wide text-muted-foreground">
                {label}
            </p>
            <p className="mt-1 text-2xl font-semibold tabular-nums">{value}</p>
        </div>
    );
}

