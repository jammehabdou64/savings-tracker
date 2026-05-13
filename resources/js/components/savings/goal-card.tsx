import { Link } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, MoreVertical, Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { show as showGoal } from '@/actions/App/Http/Controllers/GoalController';
import { formatCurrency, formatDate } from '@/lib/savings';
import type { Goal } from '@/types/savings';

type Props = {
    goal: Goal;
    onEdit: (goal: Goal) => void;
    onDelete: (goal: Goal) => void;
};

export function GoalCard({ goal, onEdit, onDelete }: Props) {
    return (
        <Card className="group relative flex flex-col gap-4 p-5 transition hover:shadow-md">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <Link
                        href={showGoal(goal.id)}
                        className="text-lg font-semibold tracking-tight hover:underline"
                    >
                        {goal.name}
                    </Link>
                    <p className="mt-1 text-xs text-muted-foreground">
                        <CalendarDays className="mr-1 inline size-3" />
                        {formatDate(goal.deadline)}
                    </p>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8 shrink-0"
                            aria-label={`Actions for ${goal.name}`}
                        >
                            <MoreVertical className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onSelect={() => onEdit(goal)}>
                            <Pencil className="size-4" />
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => onDelete(goal)}
                            className="text-destructive focus:text-destructive"
                        >
                            <Trash2 className="size-4" />
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div className="space-y-2">
                <div className="flex items-baseline justify-between text-sm">
                    <span className="font-medium">
                        {formatCurrency(goal.saved)}{' '}
                        <span className="text-muted-foreground">
                            / {formatCurrency(goal.target)}
                        </span>
                    </span>
                    <span className="font-semibold tabular-nums">
                        {goal.progress.toFixed(0)}%
                    </span>
                </div>
                <div className="h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        className={`h-full rounded-full transition-all ${
                            goal.isCompleted ? 'bg-emerald-500' : 'bg-primary'
                        }`}
                        style={{ width: `${Math.min(100, goal.progress)}%` }}
                    />
                </div>
            </div>

            {goal.isCompleted ? (
                <Badge className="w-fit gap-1 bg-emerald-100 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <CheckCircle2 className="size-3" />
                    Goal complete
                </Badge>
            ) : goal.isNotStarted ? (
                <Badge variant="secondary" className="w-fit">
                    Not started
                </Badge>
            ) : (
                <Badge variant="outline" className="w-fit">
                    In progress
                </Badge>
            )}
        </Card>
    );
}
