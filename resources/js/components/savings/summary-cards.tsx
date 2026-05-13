import { CheckCircle2, PiggyBank, Target } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { formatCurrency } from '@/lib/savings';
import type { DashboardSummary } from '@/types/savings';

const cards = [
    {
        key: 'totalSavings' as const,
        label: 'Total savings',
        icon: PiggyBank,
        format: (value: number) => formatCurrency(value),
    },
    {
        key: 'activeGoals' as const,
        label: 'Active goals',
        icon: Target,
        format: (value: number) => value.toString(),
    },
    {
        key: 'completedGoals' as const,
        label: 'Goals completed',
        icon: CheckCircle2,
        format: (value: number) => value.toString(),
    },
];

export function SummaryCards({ summary }: { summary: DashboardSummary }) {
    return (
        <div className="grid gap-4 sm:grid-cols-3">
            {cards.map(({ key, label, icon: Icon, format }) => (
                <Card
                    key={key}
                    className="flex flex-row items-center justify-between gap-4 p-5"
                >
                    <div>
                        <p className="text-sm text-muted-foreground">{label}</p>
                        <p className="mt-1 text-2xl font-semibold tracking-tight">
                            {format(summary[key])}
                        </p>
                    </div>
                    <div className="grid size-11 place-items-center rounded-full bg-primary/10 text-primary">
                        <Icon className="size-5" />
                    </div>
                </Card>
            ))}
        </div>
    );
}
