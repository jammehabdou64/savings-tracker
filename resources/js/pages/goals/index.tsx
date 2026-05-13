import { Head } from '@inertiajs/react';
import { Filter, Plus, SlidersHorizontal, Target } from 'lucide-react';
import { useMemo, useState } from 'react';
import { GoalCard } from '@/components/savings/goal-card';
import { DeleteGoalModal } from '@/components/savings/delete-goal-modal';
import { GoalFormModal } from '@/components/savings/goal-form-modal';
import { MonthlyChart } from '@/components/savings/monthly-chart';
import { SummaryCards } from '@/components/savings/summary-cards';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type {
    DashboardSummary,
    Goal,
    GoalSort,
    GoalStatusFilter,
    MonthlyDeposit,
} from '@/types/savings';

type Props = {
    goals: Goal[];
    summary: DashboardSummary;
    monthlyDeposits: MonthlyDeposit[];
};

export default function GoalsIndex({ goals, summary, monthlyDeposits }: Props) {
    const [filter, setFilter] = useState<GoalStatusFilter>('all');
    const [sort, setSort] = useState<GoalSort>('recent');
    const [formOpen, setFormOpen] = useState(false);
    const [editingGoal, setEditingGoal] = useState<Goal | null>(null);
    const [deletingGoal, setDeletingGoal] = useState<Goal | null>(null);

    const filteredGoals = useMemo(() => {
        const filtered = goals.filter((goal) => {
            if (filter === 'completed') return goal.isCompleted;
            if (filter === 'not-started') return goal.isNotStarted;
            if (filter === 'in-progress')
                return !goal.isCompleted && !goal.isNotStarted;
            return true;
        });

        const sorted = [...filtered];
        sorted.sort((a, b) => {
            switch (sort) {
                case 'deadline': {
                    if (!a.deadline) return 1;
                    if (!b.deadline) return -1;
                    return a.deadline.localeCompare(b.deadline);
                }
                case 'progress':
                    return b.progress - a.progress;
                case 'saved':
                    return b.saved - a.saved;
                case 'alphabetical':
                    return a.name.localeCompare(b.name);
                default:
                    return b.createdAt.localeCompare(a.createdAt);
            }
        });

        return sorted;
    }, [goals, filter, sort]);

    const openCreate = () => {
        setEditingGoal(null);
        setFormOpen(true);
    };

    const openEdit = (goal: Goal) => {
        setEditingGoal(goal);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Savings dashboard" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Savings dashboard
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Track your progress toward every goal.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="size-4" />
                        New goal
                    </Button>
                </header>

                <SummaryCards summary={summary} />

                <MonthlyChart data={monthlyDeposits} />

                <section className="space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-xl font-semibold tracking-tight">
                            Your goals
                        </h2>

                        <div className="flex flex-wrap gap-2">
                            <div className="flex items-center gap-2">
                                <Filter className="size-4 text-muted-foreground" />
                                <Select
                                    value={filter}
                                    onValueChange={(v) =>
                                        setFilter(v as GoalStatusFilter)
                                    }
                                >
                                    <SelectTrigger className="w-[160px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All goals
                                        </SelectItem>
                                        <SelectItem value="in-progress">
                                            In progress
                                        </SelectItem>
                                        <SelectItem value="completed">
                                            Completed
                                        </SelectItem>
                                        <SelectItem value="not-started">
                                            Not started
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-center gap-2">
                                <SlidersHorizontal className="size-4 text-muted-foreground" />
                                <Select
                                    value={sort}
                                    onValueChange={(v) => setSort(v as GoalSort)}
                                >
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="recent">
                                            Recently added
                                        </SelectItem>
                                        <SelectItem value="deadline">
                                            Deadline
                                        </SelectItem>
                                        <SelectItem value="progress">
                                            Progress
                                        </SelectItem>
                                        <SelectItem value="saved">
                                            Amount saved
                                        </SelectItem>
                                        <SelectItem value="alphabetical">
                                            Alphabetical
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    {goals.length === 0 ? (
                        <Card className="flex flex-col items-center gap-3 p-10 text-center">
                            <div className="grid size-12 place-items-center rounded-full bg-primary/10 text-primary">
                                <Target className="size-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold">
                                    No goals yet
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Create your first savings goal to start
                                    tracking progress.
                                </p>
                            </div>
                            <Button onClick={openCreate}>
                                <Plus className="size-4" />
                                Create your first goal
                            </Button>
                        </Card>
                    ) : filteredGoals.length === 0 ? (
                        <Card className="p-10 text-center text-sm text-muted-foreground">
                            No goals match this filter.
                        </Card>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {filteredGoals.map((goal) => (
                                <GoalCard
                                    key={goal.id}
                                    goal={goal}
                                    onEdit={openEdit}
                                    onDelete={setDeletingGoal}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>

            <GoalFormModal
                open={formOpen}
                onOpenChange={setFormOpen}
                goal={editingGoal}
            />
            <DeleteGoalModal
                goal={deletingGoal}
                onOpenChange={(open) => !open && setDeletingGoal(null)}
            />
        </>
    );
}

GoalsIndex.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
