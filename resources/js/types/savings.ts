export type Deposit = {
    id: number;
    amount: number;
    note: string | null;
    createdAt: string;
};

export type Goal = {
    id: number;
    name: string;
    target: number;
    deadline: string | null;
    createdAt: string;
    saved: number;
    progress: number;
    isCompleted: boolean;
    isNotStarted: boolean;
    depositCount: number;
    deposits?: Deposit[];
};

export type DashboardSummary = {
    totalSavings: number;
    activeGoals: number;
    completedGoals: number;
};

export type MonthlyDeposit = {
    month: string;
    total: number;
};

export type GoalStatusFilter = 'all' | 'in-progress' | 'completed' | 'not-started';

export type GoalSort =
    | 'recent'
    | 'deadline'
    | 'progress'
    | 'saved'
    | 'alphabetical';
