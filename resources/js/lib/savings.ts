export function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: amount % 1 === 0 ? 0 : 2,
    }).format(amount);
}

export function formatDate(value: string | null): string {
    if (!value) return 'No deadline';

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function formatMonth(yearMonth: string): string {
    const [year, month] = yearMonth.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);

    return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
}
