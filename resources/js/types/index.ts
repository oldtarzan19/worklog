import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends Record<string, unknown> {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    pendingRegistrations: number;
    flash: { success?: string };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    created_at: string;
    updated_at: string;
    role: 'admin' | 'user';
    is_active: boolean;
}

export interface TimeEntry {
    id: number;
    user_id: number;
    user_name?: string | null;
    work_date: string;
    start_time: string;
    end_time: string;
    note?: string | null;
    minutes: number;
    duration: string;
}

export interface DailySummary {
    date: string;
    minutes: number;
    duration: string;
}

export interface DashboardFilters {
    from: string;
    to: string;
    user_id?: number | null;
}

export interface WorklogKpis {
    total_minutes: number;
    total_duration: string;
    workdays: number;
    average_minutes: number;
    average_duration: string;
}

export interface UserOption {
    id: number;
    name: string;
    email: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

export interface RegistrationRequest {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
