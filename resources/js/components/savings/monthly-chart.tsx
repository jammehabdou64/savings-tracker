import { Card } from '@/components/ui/card';
import { formatCurrency, formatMonth } from '@/lib/savings';
import type { MonthlyDeposit } from '@/types/savings';

export function MonthlyChart({ data }: { data: MonthlyDeposit[] }) {
    const max = Math.max(1, ...data.map((d) => d.total));

    return (
        <Card className="p-5">
            <div className="mb-5 flex items-center justify-between">
                <h2 className="text-base font-semibold">Monthly deposits</h2>
                <span className="text-xs text-muted-foreground">
                    {data.length} {data.length === 1 ? 'month' : 'months'}
                </span>
            </div>

            {data.length === 0 ? (
                <p className="py-8 text-center text-sm text-muted-foreground">
                    No deposits yet — your monthly activity will appear here.
                </p>
            ) : (
                <div className="flex h-44 items-end gap-3 overflow-x-auto pb-2">
                    {data.map(({ month, total }) => {
                        const heightPct = Math.max(4, (total / max) * 100);
                        return (
                            <div
                                key={month}
                                className="flex min-w-12 flex-1 flex-col items-center gap-2"
                            >
                                <span className="text-xs font-medium tabular-nums text-muted-foreground">
                                    {formatCurrency(total)}
                                </span>
                                <div
                                    className="w-full rounded-t-md bg-primary/80 transition-all hover:bg-primary"
                                    style={{ height: `${heightPct}%` }}
                                    aria-label={`${formatMonth(month)}: ${formatCurrency(total)}`}
                                />
                                <span className="text-xs text-muted-foreground">
                                    {formatMonth(month)}
                                </span>
                            </div>
                        );
                    })}
                </div>
            )}
        </Card>
    );
}
